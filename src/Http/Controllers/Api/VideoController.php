<?php

namespace Biigle\Modules\Videos\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Videos\Http\Requests\UpdateVideo;
use Biigle\Modules\Videos\Http\Requests\DestroyVideo;

class VideoController extends Controller
{
    /**
     * Updates the attributes of the specified video.
     *
     * @api {put} videos/:id Update a video
     * @apiGroup Videos
     * @apiName UpdateVideos
     * @apiPermission projectAdmin
     *
     * @apiParam {Number} id The video ID.
     *
     * @apiParam (Attributes that can be updated) {String} name Name of the volume.
     * @apiParam (Attributes that can be updated) {String} url The URL of the video file. Can be a path to a storage disk like `local://videos/1.mp4` or a remote path like `https://example.com/videos/1.mp4`.
     * @apiParam (Attributes that can be updated) {String} gis_link Link to a GIS that belongs to this video.
     * @apiParam (Attributes that can be updated) {String} doi The DOI of the dataset that is represented by the new video.
     *
     * @param UpdateVideo $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVideo $request)
    {
        $video = $request->video;
        $video->name = $request->input('name', $video->name);
        $video->url = $request->input('url', $video->url);
        $video->gis_link = $request->input('gis_link', $video->gis_link);
        $video->doi = $request->input('doi', $video->doi);

        $isDirty = $video->isDirty();
        $video->save();

        if (!$this->isAutomatedRequest()) {
            return $this->fuzzyRedirect()->with('saved', $isDirty);
        }
    }

    /**
     * Delete a video.
     *
     * @api {delete} videos/:id Delete a video
     * @apiGroup Videos
     * @apiName DestroyVideos
     * @apiPermission projectAdmin
     *
     * @apiParam {Number} id The video ID.
     *
     * @apiParam (Optional parameters) {Boolean} force Must be set to `true` if the video has any annotations and should be deleted anyway.
     *
     * @param DestroyVideo $request
     * @return mixed
     */
    public function destroy(DestroyVideo $request)
    {
        $request->video->delete();
    }
}
