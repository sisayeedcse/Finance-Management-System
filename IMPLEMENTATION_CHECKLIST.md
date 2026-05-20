# SIPR GROUP MANAGEMENT PLATFORM — IMPLEMENTATION CHECKLIST

**Status:** Phase 2 - Gap Remediation
**Date:** May 18, 2026
**Target:** 100% Spec Compliance

---

## PHASE 1: CRITICAL API CONTRACT FIXES (BLOCKING)

These must be fixed first as they break frontend-to-backend integration.

### 1.1 Frontend API Endpoint Path Mismatches

| Issue                     | Current                                                    | Required                                  | File(s)                                                                                      | Priority |
| ------------------------- | ---------------------------------------------------------- | ----------------------------------------- | -------------------------------------------------------------------------------------------- | -------- |
| CSV export URL differs    | `/payments/export-csv` (FE) vs `/payments/export/csv` (BE) | Normalize to `/payments/export/csv`       | [routes/api.php#L87](routes/api.php#L87), [public/index.html#L1635](public/index.html#L1635) | CRITICAL |
| Collections endpoint path | FE calls `/collections?project_id=X`                       | Should be `/projects/{id}/collections`    | [routes/api.php#L52](routes/api.php#L52), [public/index.html#L1646](public/index.html#L1646) | CRITICAL |
| Wallet endpoint path      | FE calls `/wallet/{id}`                                    | Should be `/members/{id}/wallet`          | [routes/api.php#L37](routes/api.php#L37), [public/index.html#L1668](public/index.html#L1668) | CRITICAL |
| Passbook endpoint path    | FE calls `/wallet/{id}/passbook`                           | Should be `/members/{id}/passbook`        | [routes/api.php#L39](routes/api.php#L39), [public/index.html#L1670](public/index.html#L1670) | CRITICAL |
| Activity log POST         | FE posts to `/activity-log`                                | No POST route exists; should be read-only | [routes/api.php#L74](routes/api.php#L74), [public/index.html#L2502](public/index.html#L2502) | CRITICAL |

**Action:** Update frontend API client methods in [public/index.html](public/index.html) to match existing backend routes exactly.

---

### 1.2 Response Shape Mismatches

| Issue                 | Frontend Expectation    | Backend Returns            | File(s)                                            | Fix                                                                             |
| --------------------- | ----------------------- | -------------------------- | -------------------------------------------------- | ------------------------------------------------------------------------------- |
| Nested data.data      | `if (!data.data)` check | Returns plain array/object | [public/index.html#L2430](public/index.html#L2430) | Remove `.data` accessor; return wrapped responses from API or adjust FE parsing |
| Members endpoint      | FE expects `data.data`  | Returns Collection         | [public/index.html#L2435](public/index.html#L2435) | Ensure consistent JSON wrapper or adjust frontend                               |
| Transactions endpoint | FE expects `data.data`  | Returns Collection         | [public/index.html#L2443](public/index.html#L2443) | Ensure wrapped response                                                         |
| Projects endpoint     | FE expects `data.data`  | Returns Collection         | [public/index.html#L2467](public/index.html#L2467) | Ensure wrapped response                                                         |

**Action:** Standardize all API responses. Either:

- Option A: Wrap all controller responses in `response()->json(['data' => $collection])` format
- Option B: Remove `.data` accessors from frontend FE parsing

**Recommended:** Option A (more RESTful, allows metadata pagination in future).

---

### 1.3 Missing Frontend API Route Definitions

| Missing Method            | Purpose                     | Should Call                        | Status                                                                                          |
| ------------------------- | --------------------------- | ---------------------------------- | ----------------------------------------------------------------------------------------------- |
| `API.googleLogin`         | Handle Google OAuth token   | `POST /auth/google`                | BROKEN (referenced at [public/index.html#L2176](public/index.html#L2176), but method undefined) |
| `API.exportPaymentPdf`    | Download payment report PDF | `GET /payments/export/pdf`         | NOT IMPLEMENTED (FE has no method)                                                              |
| `API.exportWalletPdf`     | Download passbook PDF       | `GET /members/{id}/passbook/pdf`   | NOT IMPLEMENTED (FE has no method)                                                              |
| `API.approveRegistration` | Approve pending member      | `POST /registrations/{id}/approve` | EXISTS but frontend never calls it                                                              |
| `API.rejectRegistration`  | Reject pending member       | `POST /registrations/{id}/reject`  | EXISTS but frontend never calls it                                                              |
| `API.getMemberById`       | Fetch single member         | `GET /members/{id}`                | EXISTS but not used in API client object                                                        |

**Action:** Add missing methods to [public/index.html](public/index.html) API object (around L1600).

---

## PHASE 2: MISSING BACKEND ENDPOINTS & METHODS

### 2.1 Missing HTTP Methods in Existing Controllers

| Controller        | Missing Method  | Required Route                   | Spec Reference            | File                                                                                           |
| ----------------- | --------------- | -------------------------------- | ------------------------- | ---------------------------------------------------------------------------------------------- |
| WalletController  | `passpdf()`     | `GET /members/{id}/passbook/pdf` | PDF generation Part 12    | [app/Http/Controllers/WalletController.php#L33](app/Http/Controllers/WalletController.php#L33) |
| PaymentController | `exportPdf()`   | `GET /payments/export/pdf`       | Payment PDF export Part 4 | [app/Http/Controllers/PaymentController.php](app/Http/Controllers/PaymentController.php)       |
| AuthController    | `googleLogin()` | `POST /auth/google`              | Google OAuth Part 11      | [app/Http/Controllers/AuthController.php](app/Http/Controllers/AuthController.php)             |

**Details:**

#### WalletController::passpdf()

- **Current:** Returns placeholder JSON response
- **Required:** Generate PDF using Blade view + DomPDF (see [resources/views/pdfs/passbook.blade.php](resources/views/pdfs/passbook.blade.php))
- **Implementation:**

    ```php
    use Barryvdh\DomPDF\Facade\Pdf;

    public function passpdf(string $memberId)
    {
        $member = Member::findOrFail($memberId);
        $wallet = BalanceService::getMemberWallet($memberId);
        $pdf = Pdf::loadView('pdfs.passbook', compact('member', 'wallet'));
        return $pdf->download("SIPR-Passbook-{$member->name}.pdf");
    }
    ```

#### PaymentController::exportPdf()

- **Current:** Not implemented (no method exists)
- **Required:** Generate PDF of payment report for current month/year filter
- **Implementation:**
    ```php
    public function exportPdf(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $monthLabel = date('F', mktime(0,0,0,$month,1,$year));

        $members = Member::where('status','active')->get();
        $payments = Payment::where('month',$month)->where('year',$year)
                           ->get()->keyBy('member_id');

        $data = [
            'month_label' => $monthLabel,
            'year' => $year,
            'members' => $members,
            'payments' => $payments,
            'total_due' => $members->sum('monthly_due'),
            'collected' => $payments->where('status','paid')->sum('amount'),
        ];

        $pdf = Pdf::loadView('pdfs.payment-report', $data);
        return $pdf->download("SIPR-Payment-{$monthLabel}-{$year}.pdf");
    }
    ```
- **Route:** Add to [routes/api.php](routes/api.php) under finance+admin middleware

#### AuthController::googleLogin()

- **Current:** Not implemented (route references non-existent method)
- **Required:** Handle Google ID token verification and return Sanctum token
- **Implementation:**
    ```php
    public function googleLogin(Request $request)
    {
        $request->validate(['id_token' => 'required|string']);

        // Verify token using Google API or store as-is for now
        $googleUser = $request->get('google_user'); // from OAuth callback

        $member = Member::where('google_email', $googleUser['email'])
                        ->orWhere('google_uid', $googleUser['id'])
                        ->where('status', 'active')
                        ->first();

        if (!$member) {
            return response()->json(['error' => 'Not a member'], 401);
        }

        $member->update(['google_uid' => $googleUser['id']]);
        $token = $member->createToken('sipr-google')->plainTextToken;
        ActivityService::log('google_login', "{$member->name} signed in via Google", $member->id);

        return response()->json([
            'token' => $token,
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role,
            ]
        ]);
    }
    ```

### 2.2 Missing Routes

| Route                        | Method | Handler                     | Middleware                       | File                             | Status                     |
| ---------------------------- | ------ | --------------------------- | -------------------------------- | -------------------------------- | -------------------------- |
| `/payments/export/pdf`       | GET    | PaymentController@exportPdf | auth:sanctum, role:admin,finance | [routes/api.php](routes/api.php) | MISSING                    |
| `/members/{id}/passbook/pdf` | GET    | WalletController@passpdf    | auth:sanctum                     | [routes/api.php](routes/api.php) | MISSING                    |
| `/auth/google`               | POST   | AuthController@googleLogin  | none (public)                    | [routes/api.php](routes/api.php) | EXISTS but handler missing |

**Action:** Add routes to [routes/api.php](routes/api.php) L95-120 (finance+admin group for PDF exports).

---

### 2.3 Missing Activity Logging in Write Operations

The spec requires `ActivityService::log()` to be called after every write. Audit the following:

| Controller           | Method            | Currently Logged | Action Required                   |
| -------------------- | ----------------- | ---------------- | --------------------------------- |
| ProjectController    | update()          | ✗ NO             | Add logging for project updates   |
| ProjectController    | updateMilestone() | ✗ NO             | Add logging for milestone updates |
| CollectionController | store()           | ✗ NO             | Add logging for collections       |
| MemberController     | unlinkGoogle()    | ✗ NO             | Add logging for unlink action     |
| PaymentController    | store()           | ✓ YES            | Already logged                    |

**Files to modify:**

- [app/Http/Controllers/ProjectController.php](app/Http/Controllers/ProjectController.php) (update method ~L40, updateMilestone ~L63)
- [app/Http/Controllers/CollectionController.php](app/Http/Controllers/CollectionController.php) (store method ~L26)
- [app/Http/Controllers/MemberController.php](app/Http/Controllers/MemberController.php) (unlinkGoogle method ~L88)

**Sample logging:**

```php
ActivityService::log('update_project', "Updated project: {$project->name}", $request->user()->id);
ActivityService::log('update_milestone', "Updated milestone {$milestone->title}", $request->user()->id);
ActivityService::log('add_collection', "Added collection for {$project->name}", $request->user()->id);
ActivityService::log('unlink_google', "Unlinked Google account for {$member->name}", $request->user()->id);
```

---

## PHASE 3: MISSING/INCOMPLETE UI INTEGRATION

### 3.1 Frontend Firebase Remnants (Safety Issues)

| Issue                                                    | Location                                           | Fix                                                      | Priority |
| -------------------------------------------------------- | -------------------------------------------------- | -------------------------------------------------------- | -------- |
| Reference to undefined `fa.currentUser`                  | [public/index.html#L2553](public/index.html#L2553) | Replace with localStorage check or remove fallback logic | HIGH     |
| Comment "Safety: if stuck after 8s AND no Firebase user" | [public/index.html#L2549](public/index.html#L2549) | Remove or rewrite as "check localStorage token"          | HIGH     |

**Action:** Rewrite timeout safety check to use token presence, not Firebase user state.

---

### 3.2 Unimplemented Registration/Invite Flow

| Feature                            | UI Component                                                                       | Status           | Implementation Required                                             |
| ---------------------------------- | ---------------------------------------------------------------------------------- | ---------------- | ------------------------------------------------------------------- |
| New member registration request    | [public/index.html#L2228-L2252](public/index.html#L2228-L2252) showReg() / doReg() | STUBBED          | Wire doReg() to call API.createPendingRegistration() (spec Part 11) |
| Invite page with token validation  | [public/index.html#pg-invite](public/index.html)                                   | EXISTS but empty | Implement token validation + auto-fill form                         |
| Admin approval UI in control panel | Control panel page                                                                 | NOT VISIBLE      | Add pending registrations list with approve/reject buttons          |

**Details:**

#### Registration Request (doReg)

- Current: Shows "Contact admin" toast
- Required: POST to `/register-request` with name/email/phone
- Add API method:
    ```javascript
    registerRequest: (data) => apiCall("/register-request", "POST", data),
    ```
- Wire UI function:
    ```javascript
    async function doReg() {
        const res = await API.registerRequest({
            name: $("rn").value,
            email: $("re").value,
            phone: $("rp").value,
        });
        if (res) toast("Registration request sent. Awaiting approval.");
    }
    ```

#### Admin Pending Registrations List

- Where: Control panel page (id="control")
- Add section showing pending_registrations with approve/reject buttons
- Call API.getPendingRegistrations() on page load
- Wire approve/reject to control panel API methods

### 3.3 Unimplemented Change Password Modal

| Component       | Current                                                                | Required                         | File                                               |
| --------------- | ---------------------------------------------------------------------- | -------------------------------- | -------------------------------------------------- |
| Modal rendering | Renders [public/index.html#L2338-L2350](public/index.html#L2338-L2350) | ✓ Works                          |                                                    |
| doCp() function | Shows toast only                                                       | Should call API.changePassword() | [public/index.html#L2341](public/index.html#L2341) |
| API method      | Not defined in API object                                              | Add changePassword() method      | [public/index.html](public/index.html)             |
| Backend route   | Exists at PUT /me/password                                             | ✓ Exists                         | [routes/api.php#L32](routes/api.php#L32)           |

**Implementation:**

```javascript
// Add to API object (around L1600)
changePassword: ((password, password_confirmation) =>
    apiCall("/me/password", "PUT", { password, password_confirmation }),
    // Update doCp() function
    async function doCp() {
        const np = $("np").value;
        const cp2 = $("cp2").value;
        if (np.length < 6) {
            toast("Min 6 characters", false);
            return;
        }
        if (np !== cp2) {
            toast("Passwords don't match", false);
            return;
        }
        const res = await API.changePassword(np, np);
        if (res) {
            toast("Password updated!");
            closeModal();
        } else {
            toast("Failed to update password", false);
        }
    });
```

---

### 3.4 Missing PDF Download Buttons in UI

| Page             | Feature            | Current   | Required        | Implementation                                      |
| ---------------- | ------------------ | --------- | --------------- | --------------------------------------------------- |
| Monthly Payments | Payment Report PDF | No button | "📄 PDF" button | Call API.exportPaymentPdf(month, year) → download() |
| My Wallet        | Passbook PDF       | No button | "📄 PDF" button | Call API.exportWalletPdf(memberId) → download()     |

**Implementation:** Add download helpers and button click handlers in render functions for these pages.

---

## PHASE 4: CONFIGURATION & ENVIRONMENT SETUP

### 4.1 Google OAuth Configuration Missing

| Item                | Current                     | Required                               | File                                       |
| ------------------- | --------------------------- | -------------------------------------- | ------------------------------------------ |
| Google config block | ✗ Not present               | Required for Socialite                 | [config/services.php](config/services.php) |
| ENV variables       | ✗ Not present               | GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET | [.env.example](.env.example)               |
| Socialite config    | ✓ Installed (composer.json) | Activated in config                    | [config/services.php](config/services.php) |

**Action:** Add to [config/services.php](config/services.php):

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('APP_URL') . '/auth/google/callback',
],
```

**Action:** Add to [.env.example](.env.example):

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

### 4.2 Sanctum Configuration Missing

| Item                     | Current       | Required                              | File                                           |
| ------------------------ | ------------- | ------------------------------------- | ---------------------------------------------- |
| SANCTUM_STATEFUL_DOMAINS | ✗ Not set     | localhost,127.0.0.1 (and your domain) | [.env.example](.env.example)                   |
| Cors config              | ✗ Not present | Might be needed for SPA               | [config/cors.php](config/cors.php) [NOT FOUND] |

**Action:** Add to [.env.example](.env.example):

```
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### 4.3 MySQL Setup vs SQLite Default

| Setting               | Current       | Production      | File                         |
| --------------------- | ------------- | --------------- | ---------------------------- |
| DB_CONNECTION         | sqlite        | mysql           | [.env.example](.env.example) |
| DB_HOST/PORT/DATABASE | Commented out | Requires values | [.env.example](.env.example) |

**Action:** Update [.env.example](.env.example) to show MySQL by default or provide clear comments for switching.

---

## PHASE 5: DATA & UI LABEL FIDELITY

### 5.1 Sidebar Navigation Labels

| ID       | Spec Label         | Current Label | File                                               | Fix                                   |
| -------- | ------------------ | ------------- | -------------------------------------------------- | ------------------------------------- |
| finance  | "Monthly Payments" | "Finance"     | [public/index.html#L1812](public/index.html#L1812) | Change label to "💳 Monthly Payments" |
| activity | "Activity Log"     | "Audit Trail" | [public/index.html#L1819](public/index.html#L1819) | Change label to "🔍 Activity Log"     |

**Action:** Update NAVS array in [public/index.html](public/index.html) around L1806-1823.

### 5.2 Frontend Hardcoded Member Data vs Seeded Data

| Member     | FE DEF_MEMS Title    | DB Seed Title | Alignment   |
| ---------- | -------------------- | ------------- | ----------- |
| Abu Tajbit | "Operations Officer" | "Member"      | ✗ DIFFERENT |
| Fahim      | "Research Officer"   | "Secretary"   | ✗ DIFFERENT |
| Akib Ahmed | "Research Officer"   | "Adviser"     | ✗ DIFFERENT |

**Action:** Either:

- Option A: Remove DEF_MEMS fallback from FE and always fetch from API
- Option B: Align DEF_MEMS with backend seeder exactly

**Recommended:** Option A (removes duplication, ensures single source of truth).

### 5.3 Light Mode CSS Variable Precision

| Variable | Spec Value                       | Current Value    | Deviation   | File                                             |
| -------- | -------------------------------- | ---------------- | ----------- | ------------------------------------------------ |
| --txt    | #eeeeff (dark) / #0a0a2a (light) | Correct in both  | ✓ MATCH     | [public/index.html#L40](public/index.html#L40)   |
| --grn    | #5b8fff (dark) / #2244cc (light) | #1a52d8 in light | ✗ DIFFERENT | [public/index.html#L733](public/index.html#L733) |
| --mut    | #7878b8 (dark) / #5050a0 (light) | #4a52a0 in light | ✗ DIFFERENT | [public/index.html#L743](public/index.html#L743) |

**Action:** Cross-check light mode color overrides in [public/index.html](public/index.html) body.light section (L710-900) against your exact spec Part 2.2. Update any mismatches.

---

## PHASE 6: FORM REQUEST VALIDATION CLASSES

The spec calls for dedicated FormRequest classes (Part 7, scaffold). Currently all validation is inline in controllers.

### 6.1 Missing FormRequest Classes

| Class Name              | Should Validate                              | File                                          | Status  |
| ----------------------- | -------------------------------------------- | --------------------------------------------- | ------- |
| LoginRequest            | email, password                              | app/Http/Requests/LoginRequest.php            | MISSING |
| StoreTransactionRequest | member_id, type, amount, date, note          | app/Http/Requests/StoreTransactionRequest.php | MISSING |
| StorePaymentRequest     | member_id, month, year, amount, status       | app/Http/Requests/StorePaymentRequest.php     | MISSING |
| StoreProjectRequest     | name, description, type, capital, started_at | app/Http/Requests/StoreProjectRequest.php     | MISSING |
| StoreExpenseRequest     | amount, description, expense_date            | app/Http/Requests/StoreExpenseRequest.php     | MISSING |
| StoreNoticeRequest      | type, title, body                            | app/Http/Requests/StoreNoticeRequest.php      | MISSING |
| UpdateMemberRequest     | name, email, phone, title, role, monthly_due | app/Http/Requests/UpdateMemberRequest.php     | MISSING |

**Action:** Create one per file in app/Http/Requests/ with rules() method matching controller inline validation.

**Example (LoginRequest.php):**

```php
<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ];
    }
}
```

**Action:** Replace inline $request->validate() in controllers with use of these FormRequest classes.

---

## PHASE 7: PdfService CLASS CREATION

The Part 7 scaffold lists a PdfService. Currently only BalanceService, ActivityService, MemberIdService exist.

### 7.1 Missing PdfService

| Service    | Current       | Required                       | Methods                                         |
| ---------- | ------------- | ------------------------------ | ----------------------------------------------- |
| PdfService | ✗ Not present | Required for code organization | `generatePaymentReport()`, `generatePassbook()` |

**Action:** Create [app/Services/PdfService.php](app/Services/PdfService.php):

```php
<?php
namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Member;
use App\Models\Payment;

class PdfService
{
    public static function generatePaymentReport(int $month, int $year): string
    {
        $monthLabel = date('F', mktime(0,0,0,$month,1,$year));
        $members = Member::where('status','active')->get();
        $payments = Payment::where('month',$month)->where('year',$year)
                           ->get()->keyBy('member_id');

        $data = [
            'month_label' => $monthLabel,
            'year' => $year,
            'members' => $members,
            'payments' => $payments,
            'total_due' => $members->sum('monthly_due'),
            'collected' => $payments->where('status','paid')->sum('amount'),
        ];

        $pdf = Pdf::loadView('pdfs.payment-report', $data);
        return $pdf->stream("SIPR-Payment-{$monthLabel}-{$year}.pdf");
    }

    public static function generatePassbook(string $memberId): string
    {
        $member = Member::findOrFail($memberId);
        $wallet = BalanceService::getMemberWallet($memberId);

        $pdf = Pdf::loadView('pdfs.passbook', compact('member', 'wallet'));
        return $pdf->stream("SIPR-Passbook-{$member->name}.pdf");
    }
}
```

---

## PHASE 8: RESPONSE STANDARDIZATION

### 8.1 API Response Wrapper Strategy

**Decision required:** How should all API responses be shaped?

**Option A (Recommended):** Wrap all collections/data in `{ data: [...] }` for consistency:

```php
// In each controller
return response()->json(['data' => $collection]);
```

**Option B:** Return raw arrays/objects and adjust frontend parsing to not expect .data

**Recommended:** Option A for RESTful consistency. Implement in all controllers:

- [app/Http/Controllers/MemberController.php](app/Http/Controllers/MemberController.php) index()
- [app/Http/Controllers/TransactionController.php](app/Http/Controllers/TransactionController.php) index()
- [app/Http/Controllers/ProjectController.php](app/Http/Controllers/ProjectController.php) index()
- [app/Http/Controllers/GoalController.php](app/Http/Controllers/GoalController.php) index()
- [app/Http/Controllers/NoticeController.php](app/Http/Controllers/NoticeController.php) index()
- [app/Http/Controllers/DocumentController.php](app/Http/Controllers/DocumentController.php) index()
- [app/Http/Controllers/ExpenseController.php](app/Http/Controllers/ExpenseController.php) index()
- [app/Http/Controllers/PaymentController.php](app/Http/Controllers/PaymentController.php) index()
- [app/Http/Controllers/ActivityLogController.php](app/Http/Controllers/ActivityLogController.php) index()

---

## PHASE 9: BACKEND-SPECIFIC BUGS & ISSUES

### 9.1 Payment Record Update Logic

**Issue:** PaymentController::store() uses firstOrCreate, which may not update existing pending records to paid status cleanly.

**File:** [app/Http/Controllers/PaymentController.php](app/Http/Controllers/PaymentController.php) L27-50

**Current:**

```php
$payment = Payment::firstOrCreate(
    ['member_id' => $request->member_id, 'month' => $request->month, 'year' => $request->year],
    ['amount' => $request->amount, 'paid_at' => ..., 'status' => $request->status ?? 'pending']
);
```

**Issue:** If record exists as pending and we're marking it paid, firstOrCreate won't update it.

**Fix:**

```php
$payment = Payment::updateOrCreate(
    ['member_id' => $request->member_id, 'month' => $request->month, 'year' => $request->year],
    [
        'amount' => $request->amount,
        'paid_at' => $request->status === 'paid' ? ($request->paid_at ?? now()) : null,
        'status' => $request->status ?? 'pending',
        'recorded_by' => $request->user()->id,
    ]
);
```

---

### 9.2 Document URL Field Inconsistency

**Issue:** Document model supports both file uploads and URL links, but download/deletion logic only handles files.

**File:** [app/Http/Controllers/DocumentController.php](app/Http/Controllers/DocumentController.php) L51-70

**Problem:** If a URL-only document is requested for download, Storage::download() will fail.

**Fix:** In download() method, check if file_path is a URL:

```php
public function download(string $id)
{
    $document = Document::findOrFail($id);

    // If it's a URL, redirect instead of download
    if (filter_var($document->file_path, FILTER_VALIDATE_URL)) {
        return redirect($document->file_path);
    }

    // Otherwise download from storage
    return Storage::download($document->file_path, $document->original_filename);
}
```

---

### 9.3 MemberController::reset() Password Reset

**Issue:** The reset endpoint hashes all members' passwords to 'password', but should probably ask for confirmation or provide a different behavior.

**File:** [app/Http/Controllers/MemberController.php](app/Http/Controllers/MemberController.php) L82-88

**Spec requirement:** "Restore member profiles. Transactions NOT deleted." (spec Part 14, Control Panel)

**Current implementation is correct** but ensure spec intent is confirmed. If you want a different reset behavior (e.g., random passwords, email notification), this needs update.

---

## PHASE 10: TESTING & VALIDATION CHECKLIST

After all implementations, verify:

### 10.1 Functional Tests (Per Page)

- [ ] **Login Page** - Email/password login works, Google OAuth redirects correctly, error handling
- [ ] **Dashboard** - Balance count-up animation plays (900ms), all stats display, current month payments shown
- [ ] **Monthly Payments** - Filters work (month/year), CSV exports correctly, PDF exports, WhatsApp share text correct
- [ ] **Projects** - Project cards display, tabs switch, collections/milestones display correctly
- [ ] **Members** - List shows all members, expand shows passbook, avatar colors consistent
- [ ] **Notice Board** - Announcements/proposals display, post button works (for secretary/admin)
- [ ] **Expenses** - List displays, add/edit/delete works (admin/finance only)
- [ ] **Documents** - Upload works, download works, URL documents redirect
- [ ] **Goals** - Displays with correct percentages, milestones shown
- [ ] **Activity Log** - Entries grouped by date, shows for all write operations
- [ ] **About SIPR** - Displays mission/vision, founding members with roles
- [ ] **Control Panel** - Admin only, shows role descriptions, pending registrations list, member editor

### 10.2 API Integration Tests

- [ ] All endpoints return consistent response format
- [ ] All 401 auth failures handled properly
- [ ] All 403 role failures handled properly
- [ ] CSV/PDF exports stream correctly
- [ ] Activity logging occurs on every write

### 10.3 UI/UX Tests

- [ ] Dark mode all colors match spec values
- [ ] Light mode all colors match spec values
- [ ] Responsive at 800px breakpoint (sidebar hides, bottom nav shows)
- [ ] Responsive at 600px breakpoint (grids collapse)
- [ ] All animations (count-up, pulse, transitions) smooth
- [ ] Light mode toggle button works

### 10.4 Data Integrity Tests

- [ ] Members seeded exactly as per spec Part 5
- [ ] Transactions seeded exactly as per spec Part 6
- [ ] Projects seeded exactly as per spec with correct collection data
- [ ] Goals seeded with correct targets
- [ ] Avatar colors deterministic (same name = same color always)
- [ ] Currency formatting always shows ৳ symbol with thousands separator

---

## PRIORITY ROADMAP (Recommended Execution Order)

### Week 1: Critical Blocking Issues

1. **API contract fixes** (Phase 1) - ~2-3 hours
2. **Response standardization** (Phase 8) - ~1 hour
3. **Missing controller methods** (Phase 2.1) - ~3 hours
4. **Frontend API routes** (Phase 2.1) - ~2 hours

### Week 2: Backend Completion

5. **Missing routes** (Phase 2.2) - ~1 hour
6. **Activity logging** (Phase 2.3) - ~2 hours
7. **Form request classes** (Phase 6) - ~2 hours
8. **PdfService** (Phase 7) - ~2 hours

### Week 3: Configuration & UI

9. **Google OAuth config** (Phase 4.1) - ~1 hour
10. **Environment setup** (Phase 4) - ~30 minutes
11. **UI label fidelity** (Phase 5) - ~1 hour
12. **Frontend Firebase cleanup** (Phase 3.1) - ~1 hour
13. **Registration/password UI** (Phase 3.2, 3.3) - ~3 hours

### Week 4: Polish & Testing

14. **Data fidelity fixes** (Phase 5) - ~2 hours
15. **Color/styling precision** (Phase 5.3) - ~1 hour
16. **Full functional testing** (Phase 10) - ~4-6 hours
17. **Edge case handling** - ~2 hours

---

## Summary Statistics

| Category           | Total Items   | Estimated Hours |
| ------------------ | ------------- | --------------- |
| API Contract Fixes | 9             | 4-5             |
| Backend Methods    | 6             | 6-7             |
| Activity Logging   | 4             | 2-3             |
| UI Integration     | 8             | 6-7             |
| Configuration      | 6             | 1-2             |
| Data Fidelity      | 5             | 2-3             |
| Form Requests      | 7             | 3-4             |
| Service Classes    | 1             | 1-2             |
| Testing            | 30+ items     | 6-8             |
| **TOTAL**          | **76+ items** | **32-41 hours** |

---

## Sign-Off

**Completion Target:** All items implemented and tested by end of May 2026.

**Next Step:** Begin Phase 1 (API Contract Fixes) to unblock frontend-backend communication.
