<?php

namespace CATI;

/**
 * Object that represent some trees learned by random parts of samples
 */
class RandomForest
{
    /**
     * @var array - list of trees
     */
    protected array $trees;

    /**
     * @param array $trees
     * [@param float $accuracy = 1]
     */
    public function __construct (array $trees, float $accuracy = 1)
    {
        $this->trees = $trees;
        $this->accuracy = $accuracy;
    }

    /**
     * Create random forest
     * 
     * @param array $samples - samples for learning
     * [@param float $minThreshold = 0.1] - multiplier for minimal amount of training samples in range from 0 to 1
     * [@param float $maxThreshold = 0.9] - multiplier for maximal amount of training samples in range from 0 to 1
     * [@param int $forestSize = null] - number of trees in model. By default is counting by the formule 1 + sqrt(samples^1.4)
     */
    public static function create (array $samples, float $minThreshold = 0.1, float $maxThreshold = 0.9, int $forestSize = null): self
    {
        $trees = [];
        $totalSamples = [];
        $samplesCategories = [];

        foreach ($samples as $categoryName => $categorySamples)
            foreach ($categorySamples as $categorySample)
            {
                $totalSamples[] = $categorySample;
                $samplesCategories[] = $categoryName;
            }

        $samplesAmount = sizeof ($totalSamples);
        $totalAccuracy = 0;

        $forestSize = 1 + round (sqrt (pow ($samplesAmount, 1.4)));

        $thresholdMultiplier = (int)('1'. str_repeat ('0', max (strlen ($minThreshold), strlen ($maxThreshold)) - 2));
        $minThreshold *= $thresholdMultiplier;
        $maxThreshold *= $thresholdMultiplier;

        for ($i = 0; $i < $forestSize; ++$i)
        {
            $taken = [];

            for ($j = 0, $c = rand ($minThreshold, $maxThreshold) * $samplesAmount / $thresholdMultiplier; $j < $c; ++$j)
            {
                do
                {
                    $selected = rand (0, $samplesAmount - 1);
                }

                while (isset ($taken[$selected]));

                $taken[$selected] = $selected;
            }

            $treeSamples = [];

            foreach ($taken as $id)
                $treeSamples[$samplesCategories[$id]][] = $totalSamples[$id];

            $trees[$i] = Tree::train ($treeSamples);
            $totalAccuracy += $trees[$i]->accuracy ();
        }

        return new self ($trees, $totalAccuracy / $forestSize);
    }

    /**
     * Count probability for every category or it's inexistence for given features
     * 
     * @param array $features
     * 
     * @return array
     */
    public function probability (array $features): array
    {
        $features = Tree::getVariants ($features);

        $results = [];
        $nulls = 0;
        $trees = sizeof ($this->trees);

        foreach ($this->trees as $tree)
        {
            $result = $tree->predict ($features, false);

            if ($result === null)
                ++$nulls;

            else $results[$result] = ($results[$result] ?? 0) + 1;
        }

        return [
            'null' => $nulls / $trees,
            'category' => ($trees - $nulls) / $trees,
            'categories' => array_combine (array_keys ($results), array_map (function ($count) use ($trees)
            {
                return $count / $trees;
            }, $results))
        ];
    }

    /**
     * Export model's attributes
     * 
     * @return array
     */
    public function export (): array
    {
        return [
            'trees' => array_map (function (Tree $tree)
            {
                return $tree->export ();
            }, $this->trees),

            'accuracy' => $this->accuracy
        ];
    }

    /**
     * Get model's accuracy
     * 
     * @return float
     */
    public function accuracy (): float
    {
        return $this->accuracy;
    }

    /**
     * Load model from attributes
     * 
     * @param array $features
     * [@param float $accuracy = 1]
     * 
     * @return self
     */
    public static function load (array $trees, float $accuracy = 1): self
    {
        return isset ($features['trees']) && isset ($features['accuracy']) ?
            new self ($features['trees'], $features['accuracy']) :
            new self ($features, $accuracy);
    }
}
