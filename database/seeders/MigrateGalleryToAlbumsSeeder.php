<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GalleryImage;
use App\Models\GalleryAlbum;

class MigrateGalleryToAlbumsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $images = GalleryImage::whereNull('gallery_album_id')->get();

        if ($images->isEmpty()) {
            return;
        }

        // Group images by category to create albums
        $groups = $images->groupBy('category');

        foreach ($groups as $category => $categoryImages) {
            $album = GalleryAlbum::firstOrCreate(
                ['category' => $category, 'title' => ucfirst($category) . ' Collection'],
                ['is_active' => true]
            );

            foreach ($categoryImages as $image) {
                $image->update(['gallery_album_id' => $album->id]);
            }
        }
    }
}
