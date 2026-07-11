<?php

declare(strict_types=1);

/*
| Contact-support anti-spam tuning. Consumed by the ContactSupport module
| ServiceProvider, which injects these values into the framework-free
| Modules\ContactSupport\Domain\Services\SpamGuard. honeypot.php (spatie) handles
| the bot layer; this file handles the content-scoring layer.
*/
return [
    'spam' => [
        /*
         * A submission is flagged (is_spam = true) when its weighted content
         * score reaches this threshold. Tune down to be stricter, up to be more
         * forgiving. Flagged messages are still stored for manual review.
         */
        'threshold' => (int) env('CONTACT_SPAM_THRESHOLD', 5),

        /*
         * Number of URLs tolerated in a legitimate message. Each link beyond this
         * count adds to the score — link flooding is the strongest spam signal.
         */
        'max_links' => (int) env('CONTACT_SPAM_MAX_LINKS', 1),

        /*
         * Weighted keyword list (lowercase => weight). Higher weight = more
         * spammy. Deliberately EXCLUDES "help"/"support"/"question" — those are
         * the most common legitimate words on a support form and would poison the
         * filter with false positives. Phrases (with a space) are matched as a
         * substring; single words are matched on word boundaries.
         */
        'keywords' => [
            // Marketing / promotional
            'offer' => 2,
            'special offer' => 3,
            'marketing' => 2,
            'promotion' => 2,
            'promotions' => 2,
            'discount' => 2,
            'limited time' => 3,
            'act now' => 3,
            'click here' => 3,
            'buy now' => 3,
            'order now' => 3,
            'subscribe now' => 3,
            'best price' => 2,
            'cheap' => 1,
            'free' => 1,

            // SEO / backlink spam
            'seo' => 3,
            'backlink' => 4,
            'backlinks' => 4,
            'ranking' => 2,
            'rank higher' => 3,
            'traffic to your' => 3,
            'increase sales' => 3,
            'guest post' => 3,

            // Money / finance scams
            'crypto' => 3,
            'bitcoin' => 3,
            'forex' => 3,
            'investment opportunity' => 4,
            'earn money' => 4,
            'make money' => 4,
            'work from home' => 3,
            'loan' => 2,
            'casino' => 4,
            'gambling' => 3,
            'viagra' => 5,
            'pharmacy' => 3,

            // Classic scam phrasing
            'winner' => 3,
            'you have won' => 4,
            'congratulations you' => 4,
            'guaranteed' => 2,
            'risk free' => 3,
            '100% free' => 3,
        ],
    ],
];
