# Fix: BadMethodCallException - BookingPackageDetail::packageOptions()

## Problem

**Error:** `Call to undefined method App\Models\BookingPackageDetail::packageOptions()`

**Location:** EditBooking page for package bookings

**Stack Trace Entry:** Filament CheckboxList component trying to call `.relationship('packageOptions')` on BookingPackageDetail model

---

## Root Cause

The form had a **CheckboxList** component inside the **Package Details** section that was configured with:

```php
Forms\Components\CheckboxList::make('packageOptions')
    ->relationship('packageOptions', 'name')
    ->columns(2)
    ->columnSpanFull()
    ->hidden(fn (callable $get) => empty($get('package_id'))),
```

**Problem:** This CheckboxList was nested inside a `.relationship('packageDetail')` section context, which meant:
1. Filament tried to resolve the relationship from the **BookingPackageDetail** model
2. BookingPackageDetail doesn't have a `packageOptions()` method/relationship
3. The **packageOptions** BelongsToMany relationship actually exists on the **Booking** model, not BookingPackageDetail

---

## Solution

**Move the CheckboxList outside the nested Section context** so it resolves from the parent **Booking** model.

### Changes in [app/Filament/Resources/BookingResource.php](app/Filament/Resources/BookingResource.php)

**BEFORE:**
```php
Forms\Components\Section::make('Package Details')
    ->relationship('packageDetail')
    ->visible(fn (callable $get) => $get('booking_type') === 'package')
    ->schema([
        Forms\Components\Select::make('package_id')
            ->relationship('package', 'title')
            ->required()
            ->disabled($isStaff),
        // ... other fields ...
        Forms\Components\CheckboxList::make('packageOptions')  // ❌ PROBLEM: nested inside packageDetail relationship
            ->relationship('packageOptions', 'name')
            ->columns(2)
            ->columnSpanFull()
            ->hidden(fn (callable $get) => empty($get('package_id'))),
    ])->columns(2),
```

**AFTER:**
```php
Forms\Components\Section::make('Package Details')
    ->relationship('packageDetail')
    ->visible(fn (callable $get) => $get('booking_type') === 'package')
    ->schema([
        Forms\Components\Select::make('package_id')
            ->relationship('package', 'title')
            ->required()
            ->disabled($isStaff),
        // ... other fields ...
    ])->columns(2),

// ✅ NEW: Moved outside nested relationship context
Forms\Components\Section::make('Package Add-ons')
    ->visible(fn (callable $get) => $get('booking_type') === 'package')
    ->schema([
        Forms\Components\CheckboxList::make('packageOptions')
            ->relationship('packageOptions', 'name')  // ✅ Now resolves from Booking model
            ->columns(2)
            ->columnSpanFull()
            ->helperText('Select any add-on options for this package.'),
    ]),
```

---

## Why This Works

1. **packageOptions CheckboxList** is now a direct child of the form schema
2. It resolves relationships from the **root model (Booking)**, not from BookingPackageDetail
3. The Booking model **has** the `packageOptions()` BelongsToMany relationship
4. `.visible()` condition ensures it only shows when booking_type is 'package'

---

## Technical Details

### Model Relationships

**Booking.php:**
```php
public function packageOptions(): BelongsToMany
{
    return $this->belongsToMany(PackageOption::class, 'booking_package_options');
}

public function packageDetail(): HasOne
{
    return $this->hasOne(BookingPackageDetail::class);
}
```

**BookingPackageDetail.php:**
```php
public function booking(): BelongsTo
{
    return $this->belongsTo(Booking::class);
}

public function package(): BelongsTo
{
    return $this->belongsTo(Package::class);
}
// ❌ NO packageOptions() method
```

### Why Nesting Caused the Issue

When you use `.relationship('packageDetail')` at the section level, Filament enters a "nested relationship context":
- All form fields inside that section resolve from **BookingPackageDetail** model
- When CheckboxList tries to access `packageOptions`, it looks for the method on BookingPackageDetail
- Since it doesn't exist there → BadMethodCallException

---

## Files Modified

| File | Changes |
|------|---------|
| `app/Filament/Resources/BookingResource.php` | Moved CheckboxList outside packageDetail section; created new "Package Add-ons" section at root form level |

---

## Testing

### Step 1: Navigate to Edit Booking

```
Go to: Filament Admin → Bookings → Any booking with booking_type = 'package'
```

### Step 2: Verify No Error

✅ Page should load **without** BadMethodCallException error

### Step 3: Verify Form Displays Correctly

- Package Details section shows (visible when booking_type = 'package')
- Package Add-ons section appears below it (also visible when booking_type = 'package')
- CheckboxList in Package Add-ons shows available options

### Step 4: Test Selection & Save

1. Select one or more package options in CheckboxList
2. Click Save
3. Verify options are persisted to `booking_package_options` table

---

## Result

✅ EditBooking page for package bookings now loads without errors
✅ CheckboxList correctly displays available package options
✅ Relationships resolve from correct model
✅ Form state properly dehydrated/saved

