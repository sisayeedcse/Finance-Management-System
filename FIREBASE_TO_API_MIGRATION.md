# Firebase to Fetch API Migration Guide

## SIPR Group Application

**File:** `public/index.html`  
**Total Firebase Operations:** 40+ instances across the file

---

## 1. FIREBASE SDK IMPORTS & INITIALIZATION

### Location: Lines 27-36

**Current Code (Firebase SDK):**

```html
<!-- Lines 27-29 -->
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-firestore-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>

<!-- Lines 315-317 -->
firebase.initializeApp({ apiKey:"AIzaSyBAwWpntn39-CeanmYuYaCTfuRmI5BYt28",
authDomain:"sipr-group.firebaseapp.com", projectId:"sipr-group",
storageBucket:"sipr-group.firebasestorage.app",
messagingSenderId:"674053550752",
appId:"1:674053550752:web:7d4e933a2d99e8e16b4fc5" }); var
db=firebase.firestore(); var fa=firebase.auth(); var gp=new
firebase.auth.GoogleAuthProvider();
```

**Replacement:**

- Remove all Firebase script tags
- Replace with API client wrapper functions
- Remove auth provider initialization (move to API backend)

---

## 2. DATABASE READ OPERATIONS

### 2.1 Load Members (Line 553)

**Firebase Code:**

```javascript
// Line 553
function loadMembers(cb) {
    db.collection("members")
        .get()
        .then(function (snap) {
            if (snap.empty) {
                S.mems = DEF_MEMS.slice();
                if (cb) cb();
                return;
            }
            var seen = {};
            S.mems = snap.docs
                .map(function (d) {
                    return Object.assign({ id: d.id }, d.data());
                })
                .filter(function (m) {
                    if (!m.email || seen[m.email]) return false;
                    seen[m.email] = true;
                    return true;
                });
            if (cb) cb();
        })
        .catch(function () {
            S.mems = DEF_MEMS.slice();
            if (cb) cb();
        });
}
```

**Replacement Pattern:**

```javascript
// API Call instead of Firebase
function loadMembers(cb) {
    fetch("/api/members")
        .then((res) => res.json())
        .then((data) => {
            if (!data || !data.members) {
                S.mems = DEF_MEMS.slice();
                if (cb) cb();
                return;
            }
            var seen = {};
            S.mems = data.members.filter(function (m) {
                if (!m.email || seen[m.email]) return false;
                seen[m.email] = true;
                return true;
            });
            if (cb) cb();
        })
        .catch(() => {
            S.mems = DEF_MEMS.slice();
            if (cb) cb();
        });
}
```

**What needs replacing:**

- `db.collection("members").get()` → `fetch('/api/members')`
- `.then(snap => snap.docs.map(...))` → `.then(data => data.members.filter(...))`

---

### 2.2 Real-time Transactions Listener (Line 561)

**Firebase Code:**

```javascript
// Line 561-564
function loadTxs() {
    db.collection("transactions")
        .orderBy("date", "desc")
        .onSnapshot(function (snap) {
            S.txs = snap.docs.map(function (d) {
                return Object.assign({ id: d.id }, d.data());
            });
            S.txs.forEach(function (t) {
                if (
                    (!t.memberUID || t.memberUID.indexOf("SIPR26") < 0) &&
                    t.member
                ) {
                    var uid = NAME_MAP[t.member.toLowerCase().trim()];
                    if (uid)
                        db.collection("transactions")
                            .doc(t.id)
                            .update({ memberUID: uid })
                            .catch(function () {});
                }
            });
            if (
                [
                    "dashboard",
                    "payments",
                    "transactions",
                    "members",
                    "expenses",
                    "wallet",
                ].indexOf(S.tab) >= 0
            )
                render();
        });
}
```

**Replacement Pattern:**

```javascript
function loadTxs() {
    fetch("/api/transactions?sort=-date")
        .then((res) => res.json())
        .then((data) => {
            S.txs = data.transactions || [];
            S.txs.forEach(function (t) {
                if (
                    (!t.memberUID || t.memberUID.indexOf("SIPR26") < 0) &&
                    t.member
                ) {
                    var uid = NAME_MAP[t.member.toLowerCase().trim()];
                    if (uid) updateTransaction(t.id, { memberUID: uid });
                }
            });
            if (
                [
                    "dashboard",
                    "payments",
                    "transactions",
                    "members",
                    "expenses",
                    "wallet",
                ].indexOf(S.tab) >= 0
            )
                render();
        })
        .catch((e) => console.error(e));

    // For real-time: set up polling interval
    if (!window._txsInterval) {
        window._txsInterval = setInterval(() => loadTxs(), 5000);
    }
}
```

**What needs replacing:**

- `db.collection("transactions").orderBy("date","desc").onSnapshot()` → `fetch('/api/transactions?sort=-date')` + polling
- Remove Firebase snapshot listener pattern

---

### 2.3 Real-time Investments Listener (Line 568)

**Firebase Code:**

```javascript
// Line 568-571
function loadInvs() {
    db.collection("investments")
        .orderBy("date", "desc")
        .onSnapshot(function (snap) {
            S.invs = snap.docs.map(function (d) {
                return Object.assign({ id: d.id }, d.data());
            });
            if (S.tab === "dashboard" || S.tab === "investments") render();
        });
}
```

**Replacement Pattern:**

```javascript
function loadInvs() {
    fetch("/api/investments?sort=-date")
        .then((res) => res.json())
        .then((data) => {
            S.invs = data.investments || [];
            if (S.tab === "dashboard" || S.tab === "investments") render();
        })
        .catch((e) => console.error(e));

    // Set up polling
    if (!window._invsInterval) {
        window._invsInterval = setInterval(() => loadInvs(), 5000);
    }
}
```

---

### 2.4 Real-time Wallets Listener (Line 574)

**Firebase Code:**

```javascript
// Line 574-578
function loadWallets() {
    db.collection("wallets").onSnapshot(function (snap) {
        S.wallets = snap.docs.map(function (d) {
            return Object.assign({ id: d.id }, d.data());
        });
        updateWallet();
        if (S.tab === "wallet") render();
    });
}
```

**Replacement:**

```javascript
function loadWallets() {
    fetch("/api/wallets")
        .then((res) => res.json())
        .then((data) => {
            S.wallets = data.wallets || [];
            updateWallet();
            if (S.tab === "wallet") render();
        })
        .catch((e) => console.error(e));

    if (!window._walletsInterval) {
        window._walletsInterval = setInterval(() => loadWallets(), 5000);
    }
}
```

---

### 2.5 Load Announcements & Proposals (Line 2249)

**Firebase Code:**

```javascript
// Line 2249
function loadNotice() {
    db.collection("announcements")
        .orderBy("createdAt", "desc")
        .get()
        .then(function (snap) {
            window._anns = snap.docs.map(function (d) {
                return Object.assign({ id: d.id }, d.data());
            });
            if (S.nbTab === "ann") renderAnns();
        });
    db.collection("proposals")
        .orderBy("createdAt", "desc")
        .get()
        .then(function (snap) {
            window._props = snap.docs.map(function (d) {
                return Object.assign({ id: d.id }, d.data());
            });
            if (S.nbTab === "prop") renderProps();
        });
}
```

**Replacement:**

```javascript
function loadNotice() {
    Promise.all([
        fetch("/api/announcements?sort=-createdAt").then((r) => r.json()),
        fetch("/api/proposals?sort=-createdAt").then((r) => r.json()),
    ])
        .then(([annData, propData]) => {
            window._anns = annData.announcements || [];
            window._props = propData.proposals || [];
            if (S.nbTab === "ann") renderAnns();
            if (S.nbTab === "prop") renderProps();
        })
        .catch((e) => console.error(e));
}
```

---

### 2.6 Load Documents (Lines 2303+)

**Firebase Code:**

```javascript
function loadDocs() {
    db.collection("documents")
        .orderBy("uploadedAt", "desc")
        .get()
        .then(function (snap) {
            window._docs = snap.docs.map(function (d) {
                return Object.assign({ id: d.id }, d.data());
            });
            if (S.tab === "documents") render();
        })
        .catch(function () {
            window._docs = [];
        });
}
```

**Replacement:**

```javascript
function loadDocs() {
    fetch("/api/documents?sort=-uploadedAt")
        .then((res) => res.json())
        .then((data) => {
            window._docs = data.documents || [];
            if (S.tab === "documents") render();
        })
        .catch(() => {
            window._docs = [];
        });
}
```

---

### 2.7 Load Activity Log (Lines 2315+)

**Firebase Code:**

```javascript
function loadActivity() {
    db.collection("activity")
        .orderBy("ts", "desc")
        .limit(200)
        .get()
        .then(function (snap) {
            window._acts = snap.docs.map(function (d) {
                return Object.assign({ id: d.id }, d.data());
            });
            if (S.tab === "activity") render();
        })
        .catch(function () {
            window._acts = [];
        });
}
```

**Replacement:**

```javascript
function loadActivity() {
    fetch("/api/activity?sort=-ts&limit=200")
        .then((res) => res.json())
        .then((data) => {
            window._acts = data.activity || [];
            if (S.tab === "activity") render();
        })
        .catch(() => {
            window._acts = [];
        });
}
```

---

## 3. DATABASE WRITE OPERATIONS

### 3.1 Save Transaction (Line 2038-2070)

**Firebase Code:**

```javascript
// Lines 2064-2069
var p = id
    ? db.collection("transactions").doc(id).update(data)
    : db.collection("transactions").add(data);
p.then(function () {
    closeModal();
    logAct(
        id ? "edit_transaction" : "add_transaction",
        data.type + " " + data.member + " " + fmt(data.amount),
    );
    toast(id ? "Updated!" : "Added!");
}).catch(function (e) {
    toast(e.message, false);
});
```

**Replacement:**

```javascript
// Lines 2064-2069 replacement
var method = id ? "PUT" : "POST";
var endpoint = id ? `/api/transactions/${id}` : "/api/transactions";
fetch(endpoint, {
    method: method,
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
})
    .then((res) => res.json())
    .then((result) => {
        closeModal();
        logAct(
            id ? "edit_transaction" : "add_transaction",
            data.type + " " + data.member + " " + fmt(data.amount),
        );
        toast(id ? "Updated!" : "Added!");
    })
    .catch((e) => toast(e.message, false));
```

**What needs replacing:**

- `db.collection("transactions").doc(id).update(data)` → `fetch('/api/transactions/{id}', {method:'PUT', body})`
- `db.collection("transactions").add(data)` → `fetch('/api/transactions', {method:'POST', body})`

---

### 3.2 Delete Transaction (Line 2074)

**Firebase Code:**

```javascript
// Line 2074
function delTx(id) {
    if (!confirm("Delete?")) return;
    db.collection("transactions")
        .doc(id)
        .delete()
        .then(function () {
            logAct("delete_transaction", id);
            toast("Deleted.");
        })
        .catch(function (e) {
            toast(e.message, false);
        });
}
```

**Replacement:**

```javascript
function delTx(id) {
    if (!confirm("Delete?")) return;
    fetch(`/api/transactions/${id}`, { method: "DELETE" })
        .then((res) => res.json())
        .then(() => {
            logAct("delete_transaction", id);
            toast("Deleted.");
            loadTxs();
        })
        .catch((e) => toast(e.message, false));
}
```

---

### 3.3 Save Announcement (Line 2258)

**Firebase Code:**

```javascript
// Line 2258
function saveAnn() {
    var t = ($("an-t") || { value: "" }).value.trim(),
        m = ($("an-m") || { value: "" }).value.trim();
    if (!t || !m) {
        toast("Title and message required", false);
        return;
    }
    db.collection("announcements")
        .add({
            title: t,
            message: m,
            author: S.name,
            pinned: ($("an-p") || { checked: false }).checked,
            createdAt: firebase.firestore.FieldValue.serverTimestamp(),
        })
        .then(function () {
            closeModal();
            loadNotice();
            logAct("post_announcement", t);
            toast("Posted!");
        })
        .catch(function (e) {
            toast(e.message, false);
        });
}
```

**Replacement:**

```javascript
function saveAnn() {
    var t = ($("an-t") || { value: "" }).value.trim(),
        m = ($("an-m") || { value: "" }).value.trim();
    if (!t || !m) {
        toast("Title and message required", false);
        return;
    }
    fetch("/api/announcements", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            title: t,
            message: m,
            author: S.name,
            pinned: ($("an-p") || { checked: false }).checked,
            createdAt: new Date().toISOString(),
        }),
    })
        .then((res) => res.json())
        .then(() => {
            closeModal();
            loadNotice();
            logAct("post_announcement", t);
            toast("Posted!");
        })
        .catch((e) => toast(e.message, false));
}
```

**What needs replacing:**

- `db.collection("announcements").add()` → `fetch('/api/announcements', {method:'POST'})`
- `firebase.firestore.FieldValue.serverTimestamp()` → `new Date().toISOString()`

---

### 3.4 Delete Announcement (Line 2259)

**Firebase Code:**

```javascript
// Line 2259
function delAnn(id) {
    if (!confirm("Delete?")) return;
    db.collection("announcements")
        .doc(id)
        .delete()
        .then(function () {
            loadNotice();
            toast("Deleted.");
        })
        .catch(function (e) {
            toast(e.message, false);
        });
}
```

**Replacement:**

```javascript
function delAnn(id) {
    if (!confirm("Delete?")) return;
    fetch(`/api/announcements/${id}`, { method: "DELETE" })
        .then((res) => res.json())
        .then(() => {
            loadNotice();
            toast("Deleted.");
        })
        .catch((e) => toast(e.message, false));
}
```

---

### 3.5 Save Proposal (Line 2261)

**Firebase Code:**

```javascript
// Line 2261
function saveProp() {
    var t = ($("pr-t") || { value: "" }).value.trim(),
        d = ($("pr-d") || { value: "" }).value.trim();
    if (!t || !d) {
        toast("Title and description required", false);
        return;
    }
    db.collection("proposals")
        .add({
            title: t,
            description: d,
            amount: Number(($("pr-a") || { value: 0 }).value) || 0,
            date: ($("pr-dt") || { value: "" }).value,
            proposedBy: S.name,
            status: "active",
            votesYes: [],
            votesNo: [],
            createdAt: firebase.firestore.FieldValue.serverTimestamp(),
        })
        .then(function () {
            closeModal();
            loadNotice();
            logAct("submit_proposal", t);
            toast("Submitted!");
        })
        .catch(function (e) {
            toast(e.message, false);
        });
}
```

**Replacement:**

```javascript
function saveProp() {
    var t = ($("pr-t") || { value: "" }).value.trim(),
        d = ($("pr-d") || { value: "" }).value.trim();
    if (!t || !d) {
        toast("Title and description required", false);
        return;
    }
    fetch("/api/proposals", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            title: t,
            description: d,
            amount: Number(($("pr-a") || { value: 0 }).value) || 0,
            date: ($("pr-dt") || { value: "" }).value,
            proposedBy: S.name,
            status: "active",
            votesYes: [],
            votesNo: [],
            createdAt: new Date().toISOString(),
        }),
    })
        .then((res) => res.json())
        .then(() => {
            closeModal();
            loadNotice();
            logAct("submit_proposal", t);
            toast("Submitted!");
        })
        .catch((e) => toast(e.message, false));
}
```

---

### 3.6 Vote on Proposal (Line 2262)

**Firebase Code:**

```javascript
// Line 2262
function voteProposal(id, dir) {
    var p = window._props.find(function (x) {
        return x.id === id;
    });
    if (!p) return;
    var curY = (p.votesYes || []).indexOf(S.email) >= 0,
        curN = (p.votesNo || []).indexOf(S.email) >= 0;
    var u = {};
    if (dir === "yes") {
        if (curY) return;
        u.votesYes = firebase.firestore.FieldValue.arrayUnion(S.email);
        if (curN)
            u.votesNo = firebase.firestore.FieldValue.arrayRemove(S.email);
    } else {
        if (curN) return;
        u.votesNo = firebase.firestore.FieldValue.arrayUnion(S.email);
        if (curY)
            u.votesYes = firebase.firestore.FieldValue.arrayRemove(S.email);
    }
    db.collection("proposals")
        .doc(id)
        .update(u)
        .then(function () {
            loadNotice();
            toast("Vote recorded!");
        })
        .catch(function (e) {
            toast(e.message, false);
        });
}
```

**Replacement:**

```javascript
function voteProposal(id, dir) {
    var p = window._props.find((x) => x.id === id);
    if (!p) return;

    fetch(`/api/proposals/${id}/vote`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            email: S.email,
            vote: dir,
        }),
    })
        .then((res) => res.json())
        .then(() => {
            loadNotice();
            toast("Vote recorded!");
        })
        .catch((e) => toast(e.message, false));
}
```

**What needs replacing:**

- `firebase.firestore.FieldValue.arrayUnion()` → Just send the email directly
- `firebase.firestore.FieldValue.arrayRemove()` → Server handles array logic

---

### 3.7 Save Investment (Line 2013+)

**Firebase Code:**

```javascript
// Lines ~2013-2020
var p = id
    ? db.collection("investments").doc(id).update(data)
    : db.collection("investments").add(data);
p.then(function (ref) {
    closeModal();
    logAct(id ? "edit_investment" : "add_investment", data.name);
    if (!id && totalCap > 0) {
        var tx = {
            type: "invest",
            memberUID: null,
            memberName: "SIPR Fund",
            amount: totalCap,
            date: dt,
            note: "Capital → " + nm,
            addedBy: S.name,
            addedAt: now(),
        };
        db.collection("transactions")
            .add(tx)
            .then(function () {
                loadTxs();
            });
    }
    toast(id ? "Updated!" : "Project created!");
}).catch(function (e) {
    toast(e.message, false);
});
```

**Replacement:**

```javascript
var method = id ? "PUT" : "POST";
var endpoint = id ? `/api/investments/${id}` : "/api/investments";
fetch(endpoint, {
    method: method,
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
})
    .then((res) => res.json())
    .then((result) => {
        closeModal();
        logAct(id ? "edit_investment" : "add_investment", data.name);
        if (!id && totalCap > 0) {
            var tx = {
                type: "invest",
                memberUID: null,
                memberName: "SIPR Fund",
                amount: totalCap,
                date: dt,
                note: "Capital → " + nm,
                addedBy: S.name,
                addedAt: now(),
            };
            fetch("/api/transactions", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(tx),
            }).then(() => loadTxs());
        }
        toast(id ? "Updated!" : "Project created!");
    })
    .catch((e) => toast(e.message, false));
```

---

### 3.8 Save Collection Entry (Line 1654+)

**Firebase Code:**

```javascript
// Lines 1654-1670
db.collection("investments")
    .doc(invId)
    .update({ collections: cols2 })
    .then(function () {
        closeModal();
        logAct("add_collection", Number(qty) + " " + unit + " — " + inv.name);
        toast("&#x2713; Collection saved!");
    })
    .catch(function (e) {
        toast(e.message, false);
        if (btn) {
            btn.disabled = false;
            btn.textContent = "Save Collection";
        }
    });
```

**Replacement:**

```javascript
fetch(`/api/investments/${invId}/collections`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        collections: cols2,
        entry: entry,
    }),
})
    .then((res) => res.json())
    .then(() => {
        closeModal();
        logAct("add_collection", Number(qty) + " " + unit + " — " + inv.name);
        toast("✓ Collection saved!");
        loadInvs();
    })
    .catch((e) => {
        toast(e.message, false);
        if (btn) {
            btn.disabled = false;
            btn.textContent = "Save Collection";
        }
    });
```

---

### 3.9 Record Sale (Line 1763+)

**Firebase Code:**

```javascript
// Lines 1763-1775
db.collection("investments")
    .doc(invId)
    .update({ collections: cols2, sales: sales, actualReturn: newReturn })
    .then(function () {
        closeModal();
        logAct("record_sale", fmt(revenue) + " — " + inv.name);
        toast("&#x2713; Sale recorded!");
    })
    .catch(function (e) {
        toast(e.message, false);
        if (btn) {
            btn.disabled = false;
            btn.textContent = "&#x1F4B5; Record Sale";
        }
    });
```

**Replacement:**

```javascript
fetch(`/api/investments/${invId}/sales`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        collections: cols2,
        sales: sales,
        actualReturn: newReturn,
        saleEntry: saleEntry,
    }),
})
    .then((res) => res.json())
    .then(() => {
        closeModal();
        logAct("record_sale", fmt(revenue) + " — " + inv.name);
        toast("✓ Sale recorded!");
        loadInvs();
    })
    .catch((e) => {
        toast(e.message, false);
        if (btn) {
            btn.disabled = false;
            btn.textContent = "📋 Record Sale";
        }
    });
```

---

## 4. AUTHENTICATION OPERATIONS

### 4.1 Google Login (Line 402)

**Firebase Code:**

```javascript
// Lines 402-415
function gLogin() {
    var standalone =
        window.matchMedia("(display-mode:standalone)").matches ||
        !!window.navigator.standalone;
    if (mob() || standalone) {
        fa.signInWithRedirect(gp).catch(function (e) {
            showErr("lerr", e.message);
        });
        return;
    }
    fa.signInWithPopup(gp)
        .then(function (r) {})
        .catch(function (e) {
            if (
                e.code === "auth/popup-blocked" ||
                e.code === "auth/popup-closed-by-user"
            ) {
                fa.signInWithRedirect(gp).catch(function (e2) {
                    showErr("lerr", e2.message);
                });
            } else {
                showErr("lerr", e.message);
            }
        });
}
```

**Replacement:**

```javascript
function gLogin() {
    // Redirect to backend OAuth endpoint
    window.location.href = "/auth/google";
}
```

---

### 4.2 Email/Password Login (Line 417)

**Firebase Code:**

```javascript
// Lines 417-423
function eLogin() {
    var em = ($("em") || { value: "" }).value.trim().toLowerCase();
    var pw = ($("pw") || { value: "" }).value;
    if (!em || !pw) {
        showErr("lerr", "Enter email and password.");
        return;
    }
    hideErr("lerr");
    fa.signInWithEmailAndPassword(em, pw).catch(function (e) {
        showErr(
            "lerr",
            e.code === "auth/user-not-found"
                ? "Email not found."
                : e.code === "auth/wrong-password" ||
                    e.code === "auth/invalid-credential"
                  ? "Wrong password."
                  : e.message,
        );
    });
}
```

**Replacement:**

```javascript
function eLogin() {
    var em = ($("em") || { value: "" }).value.trim().toLowerCase();
    var pw = ($("pw") || { value: "" }).value;
    if (!em || !pw) {
        showErr("lerr", "Enter email and password.");
        return;
    }
    hideErr("lerr");

    fetch("/auth/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: em, password: pw }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.error) {
                showErr("lerr", data.error);
            } else {
                localStorage.setItem("token", data.token);
                S.uid = data.uid;
                S.email = em;
                handleAuthUser({ email: em, uid: data.uid });
            }
        })
        .catch((e) => showErr("lerr", e.message));
}
```

---

### 4.3 Registration (Line 460)

**Firebase Code:**

```javascript
// Lines 460-479
function doReg() {
    var name = ($("rn") || { value: "" }).value.trim();
    var email = ($("re") || { value: "" }).value.trim().toLowerCase();
    var pass = ($("rp") || { value: "" }).value;
    var code = ($("rc") || { value: "" }).value.trim().toUpperCase();
    if (!name || !email || !pass || !code) {
        showErr("lerr", "All fields required.");
        return;
    }
    if (pass.length < 6) {
        showErr("lerr", "Password min 6 characters.");
        return;
    }
    var mem = S.mems.find(function (m) {
        return m.id.toUpperCase() === code;
    });
    if (!mem) {
        showErr("lerr", "Invalid invite code. Ask Jahed.");
        return;
    }
    fa.createUserWithEmailAndPassword(email, pass)
        .then(function (cred) {
            return db.collection("members").doc(mem.id).update({
                email: email,
                name: name,
                authUid: cred.user.uid,
                status: "pending",
                _registered: true,
                pendingAt: now(),
            });
        })
        .then(function () {
            db.collection("pendingApprovals")
                .add({
                    memberId: mem.id,
                    name: name,
                    email: email,
                    requestedAt: now(),
                    status: "pending",
                })
                .catch(function () {});
            showLogin();
            hideErr("lerr");
            toast("Registration submitted! Wait for admin approval.");
        })
        .catch(function (e) {
            showErr("lerr", e.message);
        });
}
```

**Replacement:**

```javascript
function doReg() {
    var name = ($("rn") || { value: "" }).value.trim();
    var email = ($("re") || { value: "" }).value.trim().toLowerCase();
    var pass = ($("rp") || { value: "" }).value;
    var code = ($("rc") || { value: "" }).value.trim().toUpperCase();
    if (!name || !email || !pass || !code) {
        showErr("lerr", "All fields required.");
        return;
    }
    if (pass.length < 6) {
        showErr("lerr", "Password min 6 characters.");
        return;
    }
    var mem = S.mems.find((m) => m.id.toUpperCase() === code);
    if (!mem) {
        showErr("lerr", "Invalid invite code. Ask Jahed.");
        return;
    }

    fetch("/auth/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            name: name,
            email: email,
            password: pass,
            inviteCode: code,
        }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.error) {
                showErr("lerr", data.error);
            } else {
                showLogin();
                hideErr("lerr");
                toast("Registration submitted! Wait for admin approval.");
            }
        })
        .catch((e) => showErr("lerr", e.message));
}
```

---

### 4.4 Logout (Line 492)

**Firebase Code:**

```javascript
// Line 492-500
function doLogout() {
    fa.signOut().then(function () {
        S.email = "";
        S.name = "";
        S.role = "member";
        S.uid = "";
        S.txs = [];
        S.invs = [];
        S.mems = [];
        S.wallets = [];
        $("pg-app").style.display = "none";
        $("pg-login").style.display = "flex";
        $("pg-invite").style.display = "none";
    });
}
```

**Replacement:**

```javascript
function doLogout() {
    fetch("/auth/logout", { method: "POST" })
        .then(() => {
            localStorage.removeItem("token");
            S.email = "";
            S.name = "";
            S.role = "member";
            S.uid = "";
            S.txs = [];
            S.invs = [];
            S.mems = [];
            S.wallets = [];
            $("pg-app").style.display = "none";
            $("pg-login").style.display = "flex";
            $("pg-invite").style.display = "none";
        })
        .catch((e) => console.error(e));
}
```

---

### 4.5 Activity Logging (Line 583)

**Firebase Code:**

```javascript
// Line 583
db.collection("activity")
    .add({
        action: action,
        detail: detail,
        by: S.name,
        byEmail: S.email,
        role: S.role,
        ts: firebase.firestore.FieldValue.serverTimestamp(),
        iso: now(),
    })
    .catch(function () {});
```

**Replacement:**

```javascript
fetch("/api/activity", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        action: action,
        detail: detail,
        by: S.name,
        byEmail: S.email,
        role: S.role,
        ts: new Date().toISOString(),
        iso: now(),
    }),
}).catch((e) => console.error(e));
```

---

## 5. QUERY PATTERNS

### Query Patterns to Replace:

| Firebase Pattern                                          | API Alternative                                      |
| --------------------------------------------------------- | ---------------------------------------------------- |
| `db.collection("name").get()`                             | `fetch('/api/name').then(r => r.json())`             |
| `db.collection("name").orderBy("field","desc").get()`     | `fetch('/api/name?sort=-field').then(r => r.json())` |
| `db.collection("name").where("field","==","value").get()` | `fetch('/api/name?field=value').then(r => r.json())` |
| `db.collection("name").doc(id).get()`                     | `fetch('/api/name/{id}').then(r => r.json())`        |
| `db.collection("name").doc(id).set(data)`                 | `fetch('/api/name/{id}', {method:'PUT', body})`      |
| `db.collection("name").doc(id).update(data)`              | `fetch('/api/name/{id}', {method:'PATCH', body})`    |
| `db.collection("name").doc(id).delete()`                  | `fetch('/api/name/{id}', {method:'DELETE'})`         |
| `db.collection("name").add(data)`                         | `fetch('/api/name', {method:'POST', body})`          |
| `.onSnapshot()`                                           | Polling via `setInterval()` or WebSocket             |
| `firebase.firestore.FieldValue.serverTimestamp()`         | `new Date().toISOString()`                           |
| `firebase.firestore.FieldValue.arrayUnion()`              | Server handles array logic                           |

---

## 6. HELPER FUNCTIONS NEEDED

Create these wrapper functions to replace Firebase client:

```javascript
// API Wrapper Functions
async function apiCall(endpoint, method = "GET", data = null) {
    const options = {
        method: method,
        headers: { "Content-Type": "application/json" },
    };

    if (data && method !== "GET") {
        options.body = JSON.stringify(data);
    }

    const token = localStorage.getItem("token");
    if (token) {
        options.headers["Authorization"] = `Bearer ${token}`;
    }

    try {
        const response = await fetch(endpoint, options);
        if (response.status === 401) {
            doLogout();
            throw new Error("Unauthorized");
        }
        return await response.json();
    } catch (error) {
        console.error("API Error:", error);
        throw error;
    }
}

// Transaction operations
async function updateTransaction(id, data) {
    return apiCall(`/api/transactions/${id}`, "PATCH", data);
}

// Investment operations
async function updateInvestment(id, data) {
    return apiCall(`/api/investments/${id}`, "PATCH", data);
}

// Member operations
async function getMembers() {
    return apiCall("/api/members", "GET");
}
```

---

## 7. SUMMARY TABLE

| Item                | Count | Lines                                | Complexity  |
| ------------------- | ----- | ------------------------------------ | ----------- |
| SDK Imports         | 1     | 27-36                                | Low         |
| Initialize Auth     | 1     | 315-317                              | Low         |
| Read Collections    | 7     | 553, 561, 568, 574, 656, 2249, 2303+ | Medium      |
| Write Operations    | 15+   | Throughout                           | Medium-High |
| Auth Operations     | 5     | 402, 417, 460, 492, 582              | High        |
| Real-time Listeners | 3     | 561, 568, 574                        | Medium      |
| Query Operations    | 20+   | Throughout                           | Medium      |

---

## 8. MIGRATION CHECKLIST

- [ ] Remove Firebase SDK scripts from HTML
- [ ] Create API wrapper functions
- [ ] Replace all `db.collection()` calls
- [ ] Replace all `fa.auth()` calls
- [ ] Replace all `firebase.firestore.FieldValue` usage
- [ ] Replace `.onSnapshot()` with polling logic
- [ ] Test all CRUD operations
- [ ] Test authentication flow
- [ ] Test real-time updates
- [ ] Remove admin recovery Firebase-specific code
- [ ] Update environment variables for API endpoints
