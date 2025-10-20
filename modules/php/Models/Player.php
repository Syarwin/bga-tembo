<?php

namespace Bga\Games\Tembo\Models;

use Bga\Games\Tembo\Game;
use Bga\Games\Tembo\Helpers\Collection;
use Bga\Games\Tembo\Helpers\DB_Model;
use Bga\Games\Tembo\Managers\Ecosystems;
use Bga\Games\Tembo\Managers\Meeples;

/*
 * Player: all utility functions concerning a player
 */

class Player extends DB_Model
{
    protected string $table = 'player';
    protected string $primary = 'player_id';
    protected array $attributes = [
        'id' => ['player_id', 'int'],
        'no' => ['player_no', 'int'],
        'name' => 'player_name',
        'color' => 'player_color',
        'eliminated' => 'player_eliminated',
        'score' => ['player_score', 'int'],
        'scoreAux' => ['player_score_aux', 'int'],
        'zombie' => 'player_zombie',
    ];
    protected int $id;

    protected Board $board;

    public function getUiData($debug = false)
    {
        $data = parent::getUiData(true);
        $data['flowers'] = $this->getFlowers();
        $data['scores'] = $this->getScores();
        return $data;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPref(int $prefId)
    {
        return Game::get()->getGameUserPreference($this->id, $prefId);
    }

    public function getMeeples(): Collection
    {
        return Meeples::getFiltered($this->id);
    }

    public function getFlowers()
    {
        return $this->getMeeples()->where('type', ALL_COLORS);
    }

    public function getTrees(): Collection
    {
        return $this->getMeeples()->where('type', TREE);
    }

    public function getAnimals(): Collection
    {
        return $this->getMeeples()->where('type', ANIMALS);
    }

    public function board(): Board
    {
        if (!isset($this->board)) {
            $this->board = new Board($this);
        }
        return $this->board;
    }

    public function canPlayCard(FlowerCard $card): bool
    {
        return $this->board()->canPlayCard($card);
    }

    /**
     * @throws \BgaVisibleSystemException
     */
    public function updateScores(): array
    {
        $newScores = $this->getScores();
        $this->setScore($newScores['overall']);
        return $newScores;
    }

    /**
     * @throws \BgaVisibleSystemException
     */
    public function getScores(): array
    {
        // Trees
        $treesScore = count($this->getTrees()) * 2;

        // Animals
        $animalsScore = 0;
        $playerBoard = $this->board();
        $fullyFilledAreasWithAnimals = $playerBoard->getFullyFilledZones(true);
        foreach ($fullyFilledAreasWithAnimals as $area) {
            $scoreForArea = $this->getScoreForAnimal(count($area['cells']));
            $animalsScore += $scoreForArea;
        }

        // Completed & mixed areas
        $completedAreasScore = 0;
        $mixedScore = 0;
        $completedAndMixedAreas = $playerBoard->getCompletedAndMixedAreas(false);
        foreach ($completedAndMixedAreas as $area) {
            $scoreForArea = $this->getScoreForArea(count($area['cells']));
            if (count($area['colors']) > 1) {
                $mixedScore += $scoreForArea;
            } else {
                $completedAreasScore += $scoreForArea;
            }
        }

        // Unfinished areas
        $unfinishedScore = 0;
        $unfinishedAreas = array_udiff_assoc(
            $playerBoard->getZonesWithMeeples(),
            $completedAndMixedAreas,
            function ($a, $b) {
                return $a <=> $b;
            }
        );
        foreach ($unfinishedAreas as $area) {
            $scoreForArea = $this->getScoreForArea(count($area['cells']));
            $unfinishedScore += $scoreForArea;
        }
        $unfinishedAndMixedScore = $unfinishedScore + $mixedScore;

        // Overall
        $overall = $treesScore + $animalsScore + $completedAreasScore - $unfinishedAndMixedScore;
        $data = [
            'trees' => $treesScore,
            'animals' => $animalsScore,
            'completedAreas' => $completedAreasScore,
            'unfinishedAndMixed' => $unfinishedAndMixedScore,
            'overall' => $overall,
        ];
        $ecosystems = Ecosystems::getScoresForAllEcosystems($this);
        if ($ecosystems) {
            $data['overall'] = $overall + array_sum(array_values($ecosystems));;
            $data['ecosystems'] = $ecosystems;
        }
        return $data;
    }

    private function getScoreForArea(int $amountOfCells): int
    {
        return ($amountOfCells - 1) * 2;
    }

    private function getScoreForAnimal(int $amountOfCells): int
    {
        return ($amountOfCells - 1) * 3;
    }

    // public function getStat($name)
    // {
    //   $name = 'get' . Utils::ucfirst($name);
    //   return Stats::$name($this->id);
    // }
}
