<?php
namespace Xpressengine\Plugins\Comment\Models;

use Illuminate\Database\Eloquent\Collection;
use Xpressengine\Document\Models\Document;
use Xpressengine\Storage\File;
use Xpressengine\User\Models\User;

/**
 * Class Comment
 *
 * @property User $author
 * @property Target $target
 * @property Collection $files
 *
 * @package Xpressengine\Plugins\Comment\Models
 */
class Comment extends Document
{
    protected $voteType;

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function target()
    {
        return $this->hasOne(Target::class, 'docId');
    }

    public function files()
    {
        $file = new File;
        return $this->belongsToMany(File::class, $file->getFileableTable(), 'fileableId', 'fileId');
    }

    public function isAssented()
    {
        return $this->voteType === 'assent';
    }

    public function isDissented()
    {
        return $this->voteType === 'dissent';
    }

    public function setVoteType($type)
    {
        $this->voteType = $type;
    }
}
