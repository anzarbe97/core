<?php

namespace Biigle\Modules\Transects\Http\Controllers;

use File;
use Biigle\Image;
use Biigle\Http\Controllers\Views\Controller;

class ImageController extends Controller
{
    /**
     * Shows the image index page.
     *
     * @param int $id transect ID
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        $image = Image::with('transect')->findOrFail($id);

        $this->authorize('access', $image);

        $exifKeys = [
            'DateTime',
            'Model',
            'ShutterSpeedValue',
            'ApertureValue',
            'Flash',
            'GPS Latitude',
            'GPS Longitude',
            'GPS Altitude'
        ];

        $image->exif = $image->getExif();

        if (File::exists($image->url)) {
            $size = $image->getSize();
            $image->width = $size[0];
            $image->height = $size[1];
            $image->size = round(File::size($image->url) / 1e4) / 1e2;
        }

        return view('transects::images.index', [
            'image' => $image,
            'transect' => $image->transect,
            'exifKeys' => $exifKeys,
        ]);
    }
}
