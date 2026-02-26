# Email Logo Upload Fix - Debugging & Testing Guide

## Root Cause Analysis

The upload hang was caused by:
1. **Image dimension reading** - `.image()` validator was attempting to read dimensions without GD/Imagick
2. **File type validation** - `.acceptedFileTypes()` causing async validation delays
3. **Missing error handling** - No logging to track where the failure occurred

## Changes Made

### 1. [app/Filament/Pages/EmailBrandingSettings.php](app/Filament/Pages/EmailBrandingSettings.php)

**FileUpload Configuration Fixes:**
- ❌ Removed `.image()` - Causes hang when GD/Imagick not available
- ❌ Removed `.acceptedFileTypes(['image/png', ...])` - Can cause validation delays
- ✅ Added `.preserveFilenames()` - Keeps original filenames for consistency
- ✅ Added `.disk('public')` + `.directory('branding')` + `.visibility('public')`
- ✅ Set `.maxSize(5120)` - 5MB file size limit

**Enhanced save() Method:**
- Added try-catch exception handling
- Added logging for state keys and validation
- Added detailed error logging
- Improved error notifications

**Added Livewire Lifecycle Hooks:**
```php
#[On('file-upload-finished')]
public function onFileUploadFinished($fieldName = null): void
// Logs when file upload completes

public function updated($name, $value): void
// Logs when form field updates (especially FileUpload)
```

**Added Debug Placeholder:**
- Shows currently stored logo path in admin interface
- Updates after save() completes

### 2. Storage & Filesystem Setup

Created and verified:
- ✅ `/storage/app/public/branding/` directory exists
- ✅ `/storage/framework/livewire-tmp/` directory exists and is writable
- ✅ `/public/storage` symlink exists

### 3. Server Configuration Verified

✅ **PHP Upload Limits:**
- `upload_max_filesize = 20M`
- `post_max_size = 25M`
- `max_execution_time = 0` (unlimited)

✅ **PHP Extensions:**
- `fileinfo` present (works without GD/Imagick)

## Testing Steps

### Step 1: Clear Previous Upload Attempts
```bash
# Clean old uploads
rm -r storage/app/public/branding/*
rm -r storage/framework/livewire-tmp/*
```

### Step 2: Open Filament Admin

1. Navigate to: `http://localhost:8000/admin/branding-settings` (or your admin URL)
2. Scroll to "Custom Email Logo" section
3. Check the **"Stored Logo Path (Debug)"** field - should show:
   - `No logo uploaded yet` (if empty)
   - OR existing path like `branding/logo.png`

### Step 3: Upload a Logo File

1. **Prepare test image:**
   - Use any PNG, JPG, or WebP file (recommend < 2MB)
   - Example: `/public/images/site-logo.png`

2. **Click "Choose File"** in email_logo field
3. **Select your image file**
4. **Observe the upload progress:**
   - Should see upload bar move
   - Should complete quickly (not hang at "Waiting for size…")

5. **Watch browser DevTools (F12):**
   - **Network tab:** Livewire upload request should return 200 OK
   - **Console tab:** No JS errors
   - **Response:** Should be valid JSON

### Step 4: Click Save Button

1. **Form should validate** (no validation errors shown)
2. **Success notification** should appear: "Email branding saved!"
3. **Debug field should update** to show path like: `branding/logo-original-name.png`

### Step 5: Verify File Storage

```bash
# Check if file is in storage
ls -la storage/app/public/branding/

# Expected output:
# branding/
#   your_logo_name.png  (or .jpg, .webp)
```

### Step 6: Verify Database

```bash
php artisan tinker
>>> \App\Models\SiteSetting::where('key', 'email_logo')->first()
```

Should output:
```
=> App\Models\SiteSetting {#...
     key: "email_logo",
     value: "branding/your_logo_name.png",  // <-- Important!
     group: "email_branding",
     type: "file",
   }
```

### Step 7: Test Preview Rendering

1. Click **"Preview Email"** button
2. Email template should show:
   - Logo image displayed in header
   - Logo properly sized
   - No broken image icons

## Verification Checklist

- [ ] Upload completes without "Waiting for size…" hang
- [ ] No JS errors in browser console
- [ ] Network request returns 200 OK
- [ ] Success notification appears after save
- [ ] Debug field shows stored path
- [ ] File exists in `/storage/app/public/branding/`
- [ ] Database contains path in `site_settings` table
- [ ] Email preview shows logo
- [ ] Logo has correct size/styling

## Debugging if Issues Persist

### Issue: Upload still hangs

1. **Check browser console (F12):**
   - Look for JS errors
   - Check Network tab for failed requests
   - If 500 error, see next step

2. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Look for:
   - `EmailBrandingSettings save() called` (entry point)
   - `FileUpload field updated` (state changes)
   - `Saving setting:` (DB writes)
   - Any exception errors

3. **Check file permissions:**
   ```bash
   # Ensure storage is writable
   chmod -R 775 storage/ bootstrap/cache/
   ```

4. **Check if file is being stored:**
   ```bash
   ls -la storage/app/public/branding/
   ls -la storage/framework/livewire-tmp/
   ```
   If files exist in `livewire-tmp/` and not in `branding/`, Livewire is not moving files correctly.

### Issue: File appears in wrong location

- Check `.directory('branding')` is set correctly
- Files should be in: `/storage/app/public/branding/` NOT `/storage/app/public/email-assets/`

### Issue: DB not updated even after upload succeeds

1. Verify save() is being called:
   ```bash
   tail -f storage/logs/laravel.log | grep "EmailBrandingSettings"
   ```

2. Check database directly:
   ```bash
   php artisan tinker
   >>> \App\Models\SiteSetting::where('group', 'email_branding')->get()
   ```

3. If empty, something is wrong with save() method

### Issue: Preview shows no logo

1. Verify stored path is correct:
   - Check debug field shows path
   - Verify file exists at that path

2. Check Storage::url() is working:
   ```bash
   php artisan tinker
   >>> \Illuminate\Support\Facades\Storage::disk('public')->url('branding/your-logo.png')
   ```
   Should return: `/storage/branding/your-logo.png`

3. Verify public/storage symlink:
   ```bash
   ls -la public/storage
   ```
   Should be: `public/storage -> ../storage/app/public`

## Key Configuration Fixes

The fixes ensure:
✅ No dimension reading hangs (removed `.image()`)
✅ No file type validation delays (removed `.acceptedFileTypes()`)
✅ Proper error handling with detailed logging
✅ Correct file storage path (`disk('public')` + `directory('branding')`)
✅ Public access (`visibility('public')`)
✅ Original filenames preserved (`preserveFilenames()`)

## Files Changed

1. `app/Filament/Pages/EmailBrandingSettings.php`
   - Removed `.image()` validation
   - Removed `.acceptedFileTypes()` validation
   - Added `.preserveFilenames()`
   - Enhanced save() method with error handling
   - Added Livewire lifecycle hooks for debugging
   - Added debug placeholder

2. Directory structure:
   - ✅ Created `/storage/app/public/branding/`
   - ✅ Created `/storage/framework/livewire-tmp/`

## Expected Behavior

1. User uploads logo file
2. Livewire handles upload → file stored in `/storage/app/public/branding/`
3. Form state updated with filename
4. User clicks Save
5. save() method called
6. Database updated with filename
7. Success notification shown
8. Debug field shows stored path
9. reload preview → logo appears in email

## Next Steps if Issues Continue

1. Monitor logs: `tail -f storage/logs/laravel.log`
2. Check browser DevTools Network tab during upload
3. Verify all middleware filters aren't blocking uploads
4. Check if other FileUpload fields work (test with different component)
5. Consider updating Filament/Livewire if versions are outdated
