<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\Comment\Models;

use Illuminate\Database\Eloquent\Collection;
use Xpressengine\Document\Models\Document;
use Xpressengine\Storage\File;
use Xpressengine\User\Models\Guest;
use Xpressengine\User\Models\UnknownUser;
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
            if (!empty($this->userId)) {
                $author = new UnknownUser();
            } else {
                $author = new Guest();
            }
        }

        return $author;
    }

    public function getContent()
    {
        if ($this->status === static::STATUS_TRASH || $this->approved === static::APPROVED_REJECTED) {
            return xe_trans('comment::RemoveContent');
        }
        
        return $this->content;
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
