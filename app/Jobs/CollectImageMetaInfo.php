<?php

namespace Dias\Jobs;

use Carbon\Carbon;
use Dias\Jobs\Job;
use Dias\Transect;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CollectImageMetaInfo extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * The transect for which the image meta info should be collected.
     *
     * @var Transect
     */
    private $transect;

    /**
     * Create a new job instance.
     *
     * @param Transect $transect The transect for which the image meta info should be collected.
     *
     * @return void
     */
    public function __construct(Transect $transect)
    {
        $this->transect = $transect;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Not supported for remote transects.
        if ($this->transect->isRemote()) {
            return;
        }

        $images = $this->transect->images()->select('id', 'filename')->get();

        foreach ($images as $image) {
            $exif = exif_read_data($this->transect->url.'/'.$image->filename);
            if ($exif === false) continue;

            if ($this->hasTakenAtInfo($exif)) {
                $image->taken_at = new Carbon($exif['DateTimeOriginal']);
            }

            if ($this->hasGpsInfo($exif)) {
                $image->lng = $this->getGps($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
                $image->lat = $this->getGps($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
            }

            $image->save();
        }
    }

    /**
     * Check if an exif array contains a creation date
     *
     * @param  array   $exif
     * @return boolean
     */
    protected function hasTakenAtInfo(array $exif)
    {
        return array_key_exists('DateTimeOriginal', $exif);
    }

    /**
     * Check if an exif array contains GPS information
     *
     * @param  array   $exif
     * @return boolean
     */
    protected function hasGpsInfo(array $exif)
    {
        return array_key_exists('GPSLatitude', $exif) &&
            array_key_exists('GPSLatitudeRef', $exif) &&
            array_key_exists('GPSLongitude', $exif) &&
            array_key_exists('GPSLongitudeRef', $exif);
    }

    /**
     * Converts a EXIF GPS coordinate to a float
     * see: http://stackoverflow.com/a/2572991/1796523
     *
     * @param  array $exifCoord Containing fractures like `"41/1"`
     * @param  string $hemi      Hemisphere, one of `N`, `S`, `E`, or `W`
     * @return float
     */
    protected function getGps($exifCoord, $hemi)
    {
        $fracs = count($exifCoord);
        $degrees = $fracs > 0 ? $this->fracToFloat($exifCoord[0]) : 0;
        $minutes = $fracs > 1 ? $this->fracToFloat($exifCoord[1]) : 0;
        $seconds = $fracs > 2 ? $this->fracToFloat($exifCoord[2]) : 0;
        $flip = ($hemi === 'W' || $hemi === 'S') ? -1 : 1;

        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
    }

    /**
     * Converts a fracture (string) like "1/2" to a float.
     *
     * @param  [type] $frac
     * @return float
     */
    protected function fracToFloat($frac)
    {
        $parts = explode('/', $frac);
        if (count($parts) <= 0) return 0;
        if (count($parts) === 1) return $parts[0];

        return floatval($parts[0]) / floatval($parts[1]);
    }
}
