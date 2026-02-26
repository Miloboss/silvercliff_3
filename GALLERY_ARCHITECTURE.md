# GALLERY ARCHITECTURE & CONNECTIONS

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          STATIC UI PAGES                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  /public/simple_web_ui/index.html          /public/simple_web_ui/gallery.html
│  ├─ Uses: app-v2.js                        ├─ Uses: app.js                  │
│  └─ Gallery Section ID: #gallery           └─ Gallery Page                  │
│                                                                              │
│  Both load from same API: GET /api/gallery (absolute path: /api/gallery)    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                       ↓
                        Fetch: /api/gallery (JSON)
                                       ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                          LARAVEL API                                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  Route: routes/api.php                                                      │
│  GET /api/gallery  →  GalleryController@index                               │
│                                                                              │
│  Controller: app/Http/Controllers/Api/GalleryController.php                 │
│  ├─ Fetches: GalleryAlbum::with('images')->where('is_active', true)        │
│  └─ Returns: JSON with albums + nested images                              │
│                                                                              │
│  Each Image includes: image_url = asset('storage/' . $image->image_path)    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                       ↓
                    Returns JSON: GalleryAlbum[] + Images[]
                                       ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                        MODEL RELATIONSHIPS                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  GalleryAlbum (1 album)                                                      │
│  ├─ id                                                                       │
│  ├─ title: "Resort Collection"                                              │
│  ├─ category: "resort"                                                       │
│  ├─ is_active: 1                                                            │
│  └─ images() → hasMany(GalleryImage)  [Relation: 1-to-Many]                │
│                                                                              │
│  GalleryImage (multiple per album)                                           │
│  ├─ id                                                                       │
│  ├─ gallery_album_id: 1  [Foreign Key]                                       │
│  ├─ image_path: "gallery/albums/01KGS2GPT1Q3CS8BM2WGTF8ENW.jpg"             │
│  ├─ image_url (computed): "http://127.0.0.1:8000/storage/..."              │
│  ├─ is_active: 1                                                            │
│  └─ album() → belongsTo(GalleryAlbum)  [Relation: Many-to-1]                │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                       ↓
                        JSON Response with Full URLs
                                       ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                          STORAGE & FILES                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  Storage Structure:                                                          │
│  storage/app/public/                                          [Physical Dir] │
│  ├─ gallery/                                                                 │
│  │  ├─ albums/                                                              │
│  │  │  ├─ 01KGS2GPT1Q3CS8BM2WGTF8ENW.jpg                                    │
│  │  │  ├─ 01KGS2GPT6BE724Z0Z8ZCHZTF4.jpg                                    │
│  │  │  └─ ... (more images)                                                 │
│  │  └─ 01KGP0GE24F2Y3E3X1GEA28ZFB.jpg                                        │
│  ├─ activities/                                                              │
│  ├─ branding/                                                                │
│  └─ email-assets/                                                            │
│                                                                              │
│  Symlink:                                                                    │
│  public/storage/  →  storage/app/public/          [Symbolic Link]           │
│                                                                              │
│  Public Access URL:                                                         │
│  http://127.0.0.1:8000/storage/gallery/albums/01KGS2GPT1Q3CS8BM2WGTF8ENW.jpg
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                       ↓
                    Browser Renders HTML + Loads Images
                                       ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                       FRONTEND JAVASCRIPT LOGIC                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  1. Page loads → app.js or app-v2.js runs DOMContentLoaded event           │
│                                                                              │
│  2. Call loadSettings() → Fetches /api/settings for branding, etc.          │
│                                                                              │
│  3. Call loadGallery() → Fetches /api/gallery                               │
│     └─ Response stored in: currentGallery = [album1, album2, ...]          │
│                                                                              │
│  4. Call renderGallery('all' or filter) → Renders images                    │
│     └─ Creates HTML gallery items with image_url from API                   │
│     └─ Initialize GLightbox for lightbox popup on click                     │
│                                                                              │
│  5. Filter buttons click handlers → Call renderGallery(categoryFilter)       │
│     └─ Filters currentGallery by album.category                             │
│                                                                              │
│  6. Homepage only: Limit to 6-8 images (from settings)                      │
│     Gallery page: Show all images                                            │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 📦 DATA FLOW: Image Upload to Display

```
1. UPLOAD (Admin → Filament → GalleryAlbumResource)
   ├─ Select Album: "Resort Collection"
   ├─ Upload Image: photo.jpg
   └─ Filament stores at: storage/app/public/gallery/albums/HASH.jpg
      
2. DATABASE (storage/app/public/gallery/albums/HASH.jpg)
   ├─ GalleryImage record created:
   │  ├─ gallery_album_id: 1 (Resort Collection album)
   │  ├─ image_path: "gallery/albums/HASH.jpg"
   │  └─ is_active: 1
   │
   └─ Laravel computes image_url via accessor:
      └─ asset('storage/gallery/albums/HASH.jpg')
         = "http://127.0.0.1:8000/storage/gallery/albums/HASH.jpg"

3. API RESPONSE (GET /api/gallery)
   └─ Returns GalleryAlbum with nested image:
      {
        "id": 1,
        "title": "Resort Collection",
        "category": "resort",
        "images": [
          {
            "image_path": "gallery/albums/HASH.jpg",
            "image_url": "http://127.0.0.1:8000/storage/gallery/albums/HASH.jpg"
          }
        ]
      }

4. FRONTEND (app.js / app-v2.js)
   ├─ Receives JSON from API
   └─ Renders HTML gallery item:
      <a href="http://127.0.0.1:8000/storage/gallery/albums/HASH.jpg"
         class="g-item glightbox">
        <img src="http://127.0.0.1:8000/storage/gallery/albums/HASH.jpg">
      </a>

5. BROWSER RENDERING
   └─ User sees image loaded from: /public/storage/gallery/albums/HASH.jpg
      (via symlink from storage/app/public/gallery/albums/HASH.jpg)

6. LIGHTBOX (GLightbox.js)
   └─ User clicks image → Lightbox opens with href URL
```

---

## 🔗 Key Connection Points

### Frontend to Backend:
| Page | File | API Call | Grid ID | Filters | Limit |
|------|------|----------|---------|---------|-------|
| Homepage | `app-v2.js` | `/api/gallery` | `#galleryGrid` | Yes (6) | 6-8 images |
| Gallery Page | `app.js` | `/api/gallery` | `#galleryGrid` | Yes (6) | All images |

### Database Relationships:
| Table | PK | FK | Relation |
|-------|----|----|----------|
| `gallery_albums` | `id` | - | 1-to-Many to gallery_images |
| `gallery_images` | `id` | `gallery_album_id` | Many-to-1 to gallery_albums |

### Storage Paths:
| What | Physical Path | Public URL | Symlink Used |
|-----|---|---|---|
| Gallery Images | `storage/app/public/gallery/albums/*.jpg` | `/storage/gallery/albums/*.jpg` | `/public/storage/` |

---

## ✅ Why This Now Works

### Before Fix ❌:
- Seeder created orphaned images (no album_id)
- API tried to fetch albums but got empty/wrong structure
- Storage symlink missing gallery folder → 404s
- Frontend couldn't render → Gallery appeared broken

### After Fix ✅:
- Seeder creates proper album structure first
- Images linked to albums via gallery_album_id
- API returns albums + nested images with proper image_url
- Storage symlink includes all folders including gallery
- Frontend renders images with full absolute URLs
- Both pages work with same API endpoint
- Filters work because category field exists on albums

---

## 🧪 Testing Data Structure

**Current Database** (as of last verification):
- Albums: 6 (all categories covered)
  - Resort Collection: 6 images
  - Survival Collection: 2 images
  - Accommodation Collection: 2 images
  - Jungle Collection: 1 image
  - Lake Exploration: 0 images (for future uploads)
  - Elephant Collection: 2 images

**Valid Categories** (match filter buttons):
- `resort` ✓
- `jungle` ✓
- `lake` ✓
- `accommodation` ✓
- `elephant` ✓
- `survival` ✓

---

**Documentation Complete** ✅
