<?php

namespace CATI;

/**
 * Decision tree representation object
 */
class Tree
{
    /**
     * @var array - list [category => features]
     */
    protected array $features;

    /**
     * @var float - precision of the model's learning
     */
    protected float $accuracy;

    /**
     * @param array $features
     * [@param float $accuracy = 1]
     */
    public function __construct (array $features, float $accuracy = 1)
    {
        $this->features = $features;
        $this->accuracy = $accuracy;
    }

    /**
     * Train model with passed samples
     * 
     * @param array $samples - array of [category => array of samples]
     * 
     * @return self
     */
    public static function train (array $samples): self
    {
        $samples = array_map (function (array $categorySamples)
        {
            return array_map (fn ($sample) => self::getVariants ($sample), $categorySamples);
        }, $samples);

        $totalSamples = self::getTotalSamples ($samples);
        $features = [];
        $missaccuracy = $totalSamplesAmount = 0;

        foreach ($totalSamples as $categoryName => $categorySamples)
        {
            $remainedSamples = $samples[$categoryName];
            $usedSamples = [];
            $totalSamplesAmount += sizeof ($remainedSamples);
            $features[$categoryName] = [];

            foreach ($categorySamples as $sample)
                if (!in_array ($sample, $usedSamples))
                    foreach ($totalSamples as $otherCategoryName => $otherCategorySamples)
                        if ($categoryName != $categorySamples && !in_array ($sample, $otherCategorySamples))
                        {
                            $features[$categoryName][] = $sample;

                            foreach ($remainedSamples as $id => $remainedSample)
                                if (in_array ($sample, $remainedSample))
                                {
                                    $usedSamples = array_merge ($usedSamples, $remainedSamples[$id]);

                                    unset ($remainedSamples[$id]);
                                }

                            if (sizeof ($remainedSamples) == 0)
                                break 2;
                        }

            $missaccuracy += sizeof ($remainedSamples);
        }
        
        return new self ($features, $totalSamplesAmount > 0 ? 1 - $missaccuracy / $totalSamplesAmount : 1);
    }

    /**
     * Predict category of given features list
     * 
     * @param array $features
     * [@param bool $makeVariants = true] - if true, method will generate n-grams from given features
     */
    public function predict (array $features, bool $makeVariants = true): ?string
    {
        if ($makeVariants)
            $features = self::getVariants ($features);
        
        foreach ($features as $feature)
            foreach ($this->features as $categoryName => $categoryFeatures)
                if (in_array ($feature, $categoryFeatures))
                    return (string) $categoryName;

        return null;
    }

    /**
     * Get array of model's attributes that can be passed to constructor or load method
     * 
     * @return array
     */
    public function export (): array
    {
        return [
            'features' => $this->features,
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
    public static function load (array $features, float $accuracy = 1): self
    {
        return isset ($features['features']) && isset ($features['accuracy']) ?
            new self ($features['features'], $features['accuracy']) :
            new self ($features, $accuracy);
    }

    /**
     * Make list of n-grams from given items
     * 
     * @param array $items
     * 
     * @return array
     */
    public static function getVariants (array $items): array
    {
        $vatiants = [];

        for ($i = 1, $l = sizeof ($items); $i < $l; ++$i)
            for ($j = 0; $j < $l - $i + 1; ++$j)
                $vatiants[] = $i == 1 ? $items[$j] : array_slice ($items, $j, $i);

        return $vatiants;
    }

    protected static function getTotalSamples (array $samples): array
    {
        $totalSamples = [];

        foreach ($samples as $categoryName => $categorySamples)
        {
            $used = [];

            foreach ($categorySamples as $sample)
                foreach ($sample as $feature)
                {
                    $normalizedFeature = $feature;
                    $tuple = 0;
            
                    if (is_array ($feature))
                    {
                        $normalizedFeature = join ($feature);
                        $tuple = 1;
                    }
            
                    if (!isset ($used[$tuple][$normalizedFeature]))
                        $totalSamples[$categoryName][] = $feature;

                    $used[$tuple][$normalizedFeature] = ($used[$tuple][$normalizedFeature] ?? 0) + 1;
                }

            if (($totalSamples[$categoryName] ?? null) !== null)
                usort ($totalSamples[$categoryName], function ($a, $b) use ($used)
                {
                    $a = $used[(int) is_array ($a)][is_array ($a) ? join ($a) : $a];
                    $b = $used[(int) is_array ($b)][is_array ($b) ? join ($b) : $b];

                    return $b <=> $a;
                });
        }

        return $totalSamples;
    }
}
