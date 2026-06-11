<?php

namespace App\Services\Dto;

use App\Models\UserLessonProgress;

/**
 * Result of ProgressService::complete. Bundles the persisted progress row with
 * any unit reward that fired (badge / vocab bump / feature-flag activation), so
 * the calling controller can build a single response payload without re-querying.
 */
readonly class LessonCompletion
{
    /**
     * @param  array{was_new: bool, badge_key: string, vocab_delta: int, feature_flag: ?string}|null  $reward
     */
    public function __construct(
        public UserLessonProgress $progress,
        public ?array $reward,
    ) {}
}
