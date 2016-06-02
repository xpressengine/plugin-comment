<?php
namespace Xpressengine\Plugins\Comment\Models;

use Illuminate\Database\Eloquent\Collection;
use Xpressengine\Document\Models\Document;
use Xpressengine\Storage\File;
use Xpressengine\User\Models\Guest;
use Xpressengine\User\Models\User;
use Xpressengine\User\UserInterface;

/**
 * Class Comment
 *
 * @property User|null $author
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

    /**
     * Returns the author
     *
     * @return UserInterface
     */
    public function getAuthor()
    {
        if (!$author = $this->getRelationValue('author')) {
            $author = new Guest();
        }

        return $author;
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
