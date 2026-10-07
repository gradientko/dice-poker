<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['dice_poker_stats'])) {
    $_SESSION['dice_poker_stats'] = [
        'games' => 0,
        'total_score' => 0,
        'best_score' => 0,
        'yahtzees' => 0,
        'current_streak' => 0,
        'best_streak' => 0,
        'hands' => [],
    ];
}

if (!isset($_SESSION['dice_poker_round'])) {
    $_SESSION['dice_poker_round'] = createRound();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request token.';
    } else {
        $action = $_POST['action'] ?? '';
        $round = $_SESSION['dice_poker_round'];

        if ($action === 'new') {
            $_SESSION['dice_poker_round'] = createRound();

            header('Location: index.php');
            exit;
        }

        if ($round['status'] === 'playing') {
            if ($action === 'roll') {
                $held = [];

                for ($i = 0; $i < DICE_COUNT; $i++) {
                    $held[$i] = isset($_POST['hold_' . $i]);
                }

                rollDice($round, $held);
            } elseif ($action === 'score') {
                scoreRound($round);
                settleStats($_SESSION['dice_poker_stats'], $round);
            }

            $_SESSION['dice_poker_round'] = $round;
        }

        header('Location: index.php');
        exit;
    }
}

$round = $_SESSION['dice_poker_round'];
$stats = $_SESSION['dice_poker_stats'];

$averageScore = $stats['games'] > 0
    ? round($stats['total_score'] / $stats['games'], 1)
    : 0;

$rollsLeft = MAX_ROLLS - $round['rolls'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dice Poker — Pure PHP</title>
    <meta name="description" content="A server-rendered dice poker game written in pure PHP with no JavaScript.">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell">
    <header class="hero">
        <div>
            <span class="eyebrow">PURE PHP DICE POKER</span>
            <h1>Roll. Hold. Score.</h1>
            <p>
                Five dice, three rolls. Hold the ones you want and build the strongest combination you can.
            </p>
        </div>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="new">
            <button class="text-button" type="submit">New game</button>
        </form>
    </header>

    <section class="stats">
        <div>
            <span>Games</span>
            <strong><?= (int) $stats['games'] ?></strong>
        </div>
        <div>
            <span>Best score</span>
            <strong><?= (int) $stats['best_score'] ?></strong>
        </div>
        <div>
            <span>Average</span>
            <strong><?= $averageScore ?></strong>
        </div>
        <div>
            <span>Yahtzees</span>
            <strong><?= (int) $stats['yahtzees'] ?></strong>
        </div>
    </section>

    <section class="layout">
        <section class="game-card">
            <div class="game-head">
                <div>
                    <span class="eyebrow">CURRENT HAND</span>
                    <h2><?= e($round['message']) ?></h2>
                </div>

                <span class="roll-pill">
                    <?= (int) $round['rolls'] ?>/<?= MAX_ROLLS ?> rolls
                </span>
            </div>

            <?php if ($error !== null): ?>
                <div class="notice error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($round['status'] === 'finished'): ?>
                <div class="result-card">
                    <span><?= e((string) $round['result']) ?></span>
                    <strong><?= (int) $round['score'] ?> pts</strong>
                </div>
            <?php endif; ?>

            <?php if ($round['status'] === 'playing'): ?>
                <form method="post" class="dice-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="dice-row">
                        <?php for ($i = 0; $i < DICE_COUNT; $i++): ?>
                            <label class="die-card <?= !empty($round['held'][$i]) ? 'held' : '' ?>">
                                <span class="die"><?= e(diceGlyph((int) $round['dice'][$i])) ?></span>

                                <?php if ($round['rolls'] > 0 && $round['rolls'] < MAX_ROLLS): ?>
                                    <span class="hold-control">
                                        <input
                                            type="checkbox"
                                            name="hold_<?= $i ?>"
                                            <?= !empty($round['held'][$i]) ? 'checked' : '' ?>
                                        >
                                        Hold
                                    </span>
                                <?php elseif ($round['rolls'] === 0): ?>
                                    <span class="hold-control muted">Not rolled</span>
                                <?php else: ?>
                                    <span class="hold-control muted">Final</span>
                                <?php endif; ?>
                            </label>
                        <?php endfor; ?>
                    </div>

                    <div class="actions">
                        <?php if ($round['rolls'] < MAX_ROLLS): ?>
                            <button class="primary" type="submit" name="action" value="roll">
                                <?= $round['rolls'] === 0 ? 'Roll dice' : 'Roll again' ?>
                                <span><?= $rollsLeft ?> remaining</span>
                            </button>
                        <?php endif; ?>

                        <?php if ($round['rolls'] > 0): ?>
                            <button class="secondary" type="submit" name="action" value="score">
                                Score hand
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php else: ?>
                <form method="post" class="again">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="new">
                    <button type="submit">Play again</button>
                </form>
            <?php endif; ?>
        </section>

        <aside class="side">
            <section class="panel">
                <span class="eyebrow">COMBINATIONS</span>

                <div class="score-list">
                    <div><span>Yahtzee</span><strong>50</strong></div>
                    <div><span>Large straight</span><strong>40</strong></div>
                    <div><span>Four of a kind</span><strong>30</strong></div>
                    <div><span>Full house</span><strong>25</strong></div>
                    <div><span>Small straight</span><strong>20</strong></div>
                    <div><span>Three of a kind</span><strong>18</strong></div>
                    <div><span>Two pair</span><strong>14</strong></div>
                    <div><span>Pair</span><strong>8</strong></div>
                </div>
            </section>

            <section class="panel">
                <span class="eyebrow">STREAK</span>
                <h3><?= (int) $stats['current_streak'] ?>×</h3>
                <p>
                    Strong hands — full house, four of a kind, large straight and Yahtzee —
                    extend the streak.
                </p>
            </section>

            <section class="panel">
                <span class="eyebrow">BEST STREAK</span>
                <h3><?= (int) $stats['best_streak'] ?>×</h3>
            </section>

            <section class="panel">
                <span class="eyebrow">PURE SERVER SIDE</span>
                <p>
                    No JavaScript. Rolls, held dice, scoring and statistics are all handled
                    with PHP forms and <code>$_SESSION</code>.
                </p>
            </section>
        </aside>
    </section>

    <footer>
        <span>PHP sessions · random_int · poker combinations · no JavaScript</span>
        <span>dice-poker</span>
    </footer>
</main>
</body>
</html>
