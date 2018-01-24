<?php

namespace Biigle;

use DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * An annotation session groups multiple annotations of a volume based on their
 * creation date.
 */
class AnnotationSession extends Model
{
    /**
     * Validation rules for updating an annotation session.
     *
     * @var array
     */
    public static $storeRules = [
        'name' => 'required',
        'starts_at' => 'required|date',
        'ends_at' => 'required|date|after:starts_at',
        'hide_other_users_annotations' => 'filled|boolean',
        'hide_own_annotations' => 'filled|boolean',
    ];

    /**
     * Validation rules for updating an annotation session.
     *
     * @var array
     */
    public static $updateRules = [
        'name' => 'filled',
        'starts_at' => 'filled|date',
        'ends_at' => 'filled|date',
        'hide_other_users_annotations' => 'filled|boolean',
        'hide_own_annotations' => 'filled|boolean',
        'force' => 'filled|boolean',
    ];

    /**
     * Validation rules for destroying an annotation session.
     *
     * @var array
     */
    public static $destroyRules = [
        'force' => 'filled|boolean',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'hide_other_users_annotations' => 'boolean',
        'hide_own_annotations' => 'boolean',
        'volume_id' => 'int',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'starts_at_iso8601',
        'ends_at_iso8601',
    ];

    /**
     * Scope a query to only include active annotation sessions.
     *
     * @param Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();

        return $query->where('annotation_sessions.starts_at', '<=', $now)
            ->where('annotation_sessions.ends_at', '>', $now);
    }

    /**
     * The project, this annotation session belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the annotations of the image (with labels), filtered by the restrictions of
     * this annotation session.
     *
     * @param Image $image The image to get the annotations from
     * @param User $user The user to whom the restrictions should apply ('own' user)
     *
     * @return Illuminate\Support\Collection
     */
    public function getImageAnnotations(Image $image, User $user)
    {
        $query = Annotation::allowedBySession($this, $user)
            ->where('annotations.image_id', $image->id);

        /*
         * If both hide_other_users_annotations and hide_own_annotations is true,
         * allowedBySession already filters out all old annotations and only those
         * annotations are kept, that belong to this session. We therefore only need
         * to perform the hide_other_users_annotations filtering.
         * This is the reason why there is no special case for both true in the following
         * if else block.
         */
        if ($this->hide_other_users_annotations) {
            // Hide all annotation labels of other users.
            $query->with(['labels' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }]);
        } elseif ($this->hide_own_annotations) {
            // Take only labels of this session or any labels of other users.
            $query->with(['labels' => function ($query) use ($user) {
                // Wrap this in a where because the default query already has a where.
                $query->where(function ($query) use ($user) {
                    $query->where(function ($query) {
                        $query->where('created_at', '>=', $this->starts_at)
                            ->where('created_at', '<', $this->ends_at);
                    })
                    ->orWhere('user_id', '!=', $user->id);
                });
            }]);
        } else {
            $query->with('labels');
        }

        return $query->get();
    }

    /**
     * Get a query for all annotations that belong to this session.
     *
     * This is **not** an Eloquent relation!
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function annotations()
    {
        return Annotation::where(function ($query) {
            // All annotations of the associated project....
            return $query->whereIn('project_volume_id', function ($query) {
                    $query->select('id')
                        ->from('project_volume')
                        ->where('project_id', $this->project_id);
                })
                // ...that were created between the start and end date.
                ->where('created_at', '>=', $this->starts_at)
                ->where('created_at', '<', $this->ends_at);
        });
    }

    /**
     * Check if the given user is allowed to access the annotation if this annotation
     * session is active.
     *
     * @param Annotation $annotation
     * @param User $user
     * @return bool
     */
    public function allowsAccess(Annotation $annotation, User $user)
    {
        if ($this->hide_own_annotations && $this->hide_other_users_annotations) {
            return $annotation->created_at >= $this->starts_at &&
                $annotation->created_at < $this->ends_at &&
                $annotation->labels()->where('user_id', $user->id)->exists();
        } elseif ($this->hide_own_annotations) {
            return ($annotation->created_at >= $this->starts_at && $annotation->created_at < $this->ends_at) ||
                $annotation->labels()->where('user_id', '!=', $user->id)->exists();
        } elseif ($this->hide_other_users_annotations) {
            return $annotation->labels()->where('user_id', $user->id)->exists();
        }

        return true;
    }

    /**
     * Set the start date.
     *
     * @param mixed $value The date (must be parseable by Carbon)
     */
    public function setStartsAtAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['starts_at'] = null;
        } else {
            $this->attributes['starts_at'] = Carbon::parse($value)->tz(config('app.timezone'));
        }
    }

    /**
     * Set the end date.
     *
     * @param mixed $value The date (must be parseable by Carbon)
     */
    public function setEndsAtAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['ends_at'] = null;
        } else {
            $this->attributes['ends_at'] = Carbon::parse($value)->tz(config('app.timezone'));
        }
    }

    /**
     * Get the start date formatted as ISO8601 string.
     *
     * @return string
     */
    public function getStartsAtIso8601Attribute()
    {
        return $this->starts_at->toIso8601String();
    }

    /**
     * Get the end date formatted as ISO8601 string.
     *
     * @return string
     */
    public function getEndsAtIso8601Attribute()
    {
        return $this->ends_at->toIso8601String();
    }
}
