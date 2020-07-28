<?php

namespace Biigle;

use Biigle\Contracts\ImageAnnotation as AnnotationContract;
use Biigle\Traits\HasPointsAttribute;
use DB;

/**
 * An image annotation is a region of an image that can be labeled by the users.
 * It consists of one or many points and has a specific shape.
 */
class ImageAnnotation extends Annotation implements AnnotationContract
{
     /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'image_id' => 'int',
        'shape_id' => 'int',
        'points' => 'array',
    ];

    /**
     * The image, this annotation belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * The file, this annotation belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file()
    {
        return $this->image();
    }

    /**
     * Get the file_id attribute
     *
     * @return int
     */
    public function getFileIdAttribute()
    {
        return $this->image_id;
    }

    /**
     * The labels, this annotation got assigned by the users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function labels()
    {
        return $this->hasMany(ImageAnnotationLabel::class, 'annotation_id');
    }

        /**
     * {@inheritdoc}
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    /**
     * {@inheritdoc}
     */
    public function getShape(): Shape
    {
        return $this->shape;
    }

    /**
     * {@inheritdoc}
     */
    public function getImage(): Image
    {
        return $this->image;
    }
}
