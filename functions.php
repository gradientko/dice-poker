<?php
declare(strict_types=1);

const DICE_COUNT = 5;
const MAX_ROLLS = 3;

function createRound(): array
{
    return [
        'dice' => [1, 1, 1, 1, 1],
        'held' => [false, false, false, false, false],
        'rolls' => 0,
        'status' => 'playing',
        'scored' => false,
        'result' => null,
        'score' => 0,
        'message' => 'Roll the dice.',
    ];
}

function rollDice(array &$round, array $held): void
{
    if ($round['status'] !== 'playing' || $round['rolls'] >= MAX_ROLLS) {
        return;
    }

    for ($i = 0; $i < DICE_COUNT; $i++) {
        $round['held'][$i] = $held[$i] ?? false;

        if (!$round['held'][$i] || $round['rolls'] === 0) {
            $round['dice'][$i] = random_int(1, 6);
        }
    }

    $round['rolls']++;

    if ($round['rolls'] >= MAX_ROLLS) {
        $round['message'] = 'Final roll. Score the hand.';
    } else {
        $left = MAX_ROLLS - $round['rolls'];
        $round['message'] = "Choose dice to hold. {$left} roll" . ($left === 1 ? '' : 's') . ' left.';
    }
}

function scoreRound(array &$round): void
{
    if ($round['status'] !== 'playing' || $round['rolls'] === 0) {
        return;
    }

    $evaluation = evaluateHand($round['dice']);

    $round['status'] = 'finished';
    $round['scored'] = true;
    $round['result'] = $evaluation['name'];
    $round['score'] = $evaluation['score'];
    $round['message'] = $evaluation['message'];
}

function evaluateHand(array $dice): array
{
    sort($dice);

    $counts = array_count_values($dice);
    rsort($counts);

    $unique = array_values(array_unique($dice));
    sort($unique);

    $isLargeStraight = $unique === [1, 2, 3, 4, 5] || $unique === [2, 3, 4, 5, 6];
    $isSmallStraight = containsStraight($unique, 4);

    if ($counts[0] === 5) {
        return result('Yahtzee', 50, 'Yahtzee! Five of a kind.');
    }

    if ($isLargeStraight) {
        return result('Large straight', 40, 'Large straight.');
    }

    if ($counts[0] === 4) {
        return result('Four of a kind', 30, 'Four of a kind.');
    }

    if ($counts[0] === 3 && ($counts[1] ?? 0) === 2) {
        return result('Full house', 25, 'Full house.');
    }

    if ($isSmallStraight) {
        return result('Small straight', 20, 'Small straight.');
    }

    if ($counts[0] === 3) {
        return result('Three of a kind', 18, 'Three of a kind.');
    }

    if ($counts[0] === 2 && ($counts[1] ?? 0) === 2) {
        return result('Two pair', 14, 'Two pair.');
    }

    if ($counts[0] === 2) {
        return result('Pair', 8, 'One pair.');
    }

    return result('High dice', array_sum($dice), 'No combination. High dice score.');
}

function containsStraight(array $unique, int $length): bool
{
    if (count($unique) < $length) {
        return false;
    }

    for ($start = 0; $start <= count($unique) - $length; $start++) {
        $slice = array_slice($unique, $start, $length);
        $expected = range($slice[0], $slice[0] + $length - 1);

        if ($slice === $expected) {
            return true;
        }
    }

    return false;
}

function result(string $name, int $score, string $message): array
{
    return [
        'name' => $name,
        'score' => $score,
        'message' => $message,
    ];
}

function settleStats(array &$stats, array &$round): void
{
    if (
        $round['status'] !== 'finished'
        || empty($round['scored'])
        || !empty($round['settled'])
    ) {
        return;
    }

    $stats['games']++;
    $stats['total_score'] += $round['score'];
    $stats['best_score'] = max($stats['best_score'], $round['score']);

    $name = $round['result'];

    if (!isset($stats['hands'][$name])) {
        $stats['hands'][$name] = 0;
    }

    $stats['hands'][$name]++;

    if ($name === 'Yahtzee') {
        $stats['yahtzees']++;
        $stats['current_streak']++;
        $stats['best_streak'] = max(
            $stats['best_streak'],
            $stats['current_streak']
        );
    } elseif (in_array($name, ['Large straight', 'Four of a kind', 'Full house'], true)) {
        $stats['current_streak']++;
        $stats['best_streak'] = max(
            $stats['best_streak'],
            $stats['current_streak']
        );
    } else {
        $stats['current_streak'] = 0;
    }

    $round['settled'] = true;
}

function diceGlyph(int $value): string
{
    return match ($value) {
        1 => '⚀',
        2 => '⚁',
        3 => '⚂',
        4 => '⚃',
        5 => '⚄',
        6 => '⚅',
        default => '?',
    };
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
