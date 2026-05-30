<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'source_id',
        'capital',
        'returned',
        'expected',
        'team',
        'started_at',
        'capitalSource',
        'projectManagerId',
        'projectManagerName',
        'teamEntries',
        'teamMembers',
        'collections',
        'sales',
        'buyers',
        'projectExpenses',
        'capitalEntries',
        'phases',
        'partner',
        'amount',
        'capitalDeployed',
        'expectedReturn',
        'actualReturn',
        'sector',
        'date',
        'notes',
        'source_payload',
    ];

    protected $casts = [
        'capital' => 'decimal:2',
        'returned' => 'decimal:2',
        'expected' => 'decimal:2',
        'started_at' => 'date',
        'teamEntries' => 'array',
        'teamMembers' => 'array',
        'collections' => 'array',
        'sales' => 'array',
        'buyers' => 'array',
        'projectExpenses' => 'array',
        'capitalEntries' => 'array',
        'phases' => 'array',
        'source_payload' => 'array',
    ];

    public function getAmountAttribute()
    {
        return $this->capital;
    }

    public function setAmountAttribute($value): void
    {
        $this->attributes['capital'] = $value;
    }

    public function getCapitalDeployedAttribute()
    {
        return $this->capital;
    }

    public function setCapitalDeployedAttribute($value): void
    {
        $this->attributes['capital'] = $value;
    }

    public function getExpectedReturnAttribute()
    {
        return $this->expected;
    }

    public function setExpectedReturnAttribute($value): void
    {
        $this->attributes['expected'] = $value;
    }

    public function getActualReturnAttribute()
    {
        return $this->returned;
    }

    public function setActualReturnAttribute($value): void
    {
        $this->attributes['returned'] = $value;
    }

    public function getSectorAttribute()
    {
        return $this->type;
    }

    public function setSectorAttribute($value): void
    {
        $this->attributes['type'] = $value;
    }

    public function getDateAttribute()
    {
        return !empty($this->attributes['started_at'])
            ? substr((string) $this->attributes['started_at'], 0, 10)
            : null;
    }

    public function setDateAttribute($value): void
    {
        $this->attributes['started_at'] = $value;
    }

    public function getNotesAttribute()
    {
        return $this->description;
    }

    public function setNotesAttribute($value): void
    {
        $this->attributes['description'] = $value;
    }

    public function collections()
    {
        return $this->hasMany(ProjectCollection::class, 'project_id');
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id');
    }
}
