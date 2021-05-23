<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * @package     CATI Tree
 * @copyright   2021 Podvirnyy Nikita (Observer KRypt0n_)
 * @license     GNU GPL-3.0 <https://www.gnu.org/licenses/gpl-3.0.html>
 * @author      Podvirnyy Nikita (Observer KRypt0n_)
 * 
 * Contacts:
 *
 * Email: <suimin.tu.mu.ga.mi@gmail.com>
 * VK:    <https://vk.com/technomindlp>
 *        <https://vk.com/hphp_convertation>
 * 
 */

namespace CATI;

class Tree
{
    /**
     * Array of categories' patterns
     */
    protected array $patterns = [];

    /**
     * List of training samples
     */
    protected array $dataset = [];

    /**
     * List of samples' titles
     */
    protected array $labels = [];

    /**
     * Structure's constructor
     * 
     * [@param array $patterns = []] - array of patterns or dataset samples if second parameter declared
     * [@param array $labels = null] - list of samples' titles
     * 
     * @example
     * 
     * $tree = new Tree;
     * 
     * $tree = new Tree ([
     *     'category 1' => [
     *         'feature 1',
     *         'feature 2'
     *     ]
     * ]);
     * 
     * $tree = new Tree ([
     *     ['a', 'b', 'c'],
     *     ['c', 'b', 'd'],
     *     ['e']
     * ], [
     *     'category 1',
     *     'category 2',
     *     'category 3',
     *     'category 4'
     * ]);
     */
    public function __construct (array $patterns = [], array $labels = null)
    {
        # Define patterns if labels not passed
        if ($labels === null)
            $this->patterns = $patterns;

        # Otherwise define samples and titles for future training
        else
        {
            $this->dataset = $patterns;
            $this->labels  = $labels;
        }
    }

    /**
     * Add new sample to the training base
     * 
     * @param string $name  - name of sample's category
     * @param array $sample - sample
     * 
     * @return self
     * 
     * @example
     * 
     * $tree = (new Tree)->addSample ('category 1', ['list', 'of', 'features']);
     * 
     * $tree = (new Tree)->addSample ('category 1', [
     *     ['first', 'list', 'of', 'features'],
     *     ['second', 'list', 'of', 'features']
     * ]);
     */
    public function addSample (string $name, array $sample): self
    {
        # Use $sample as a list of samples if it is a multidimensional array
        if (is_array (current ($sample)))
            foreach ($sample as $t)
            {
                $this->dataset[] = $t;
                $this->labels[]  = $name;
            }
        
        else
        {
            $this->dataset[] = $sample;
            $this->labels[]  = $name;
        }

        return $this;
    }

    /**
     * Prepare (train) tree for predictions
     * 
     * I will not explain how it works
     * 
     * @return self
     */
    public function prepare (): self
    {
        $samples = $this->getSamples ();
        $samplesNormalized = $samplesFeatures = [];
        $names = [];

        foreach ($samples as $name => $items)
            foreach ($items as $id => $item)
            {
                $sampleName = $name . $id;
                $names[$sampleName] = $name;
                
                $samplesNormalized[$sampleName] = $item;
                
                foreach ($item as $i)
                    $samplesFeatures[1][$i] = $i;

                for ($i = 2, $l = sizeof ($item); $i <= $l; ++$i)
                {
                    $ngrams = $this->ngrams ($item, $i);

                    foreach ($ngrams as $j)
                        $samplesFeatures[$i][$j] = $j;

                    $samplesNormalized[$sampleName] = array_merge ($samplesNormalized[$sampleName], $ngrams);
                }
            }

        $samplesFeaturesNormalized = [];

        foreach ($samplesFeatures as $features)
            $samplesFeaturesNormalized = array_merge ($samplesFeaturesNormalized, $features);

        $patterns = $this->identFeatures ($samplesNormalized, $samplesFeaturesNormalized);
        $this->patterns = [];

        foreach ($patterns as $name => $pattern)
            $this->patterns[$names[$name]][] = $pattern;

        return $this;
    }

    /**
     * Predict category title
     * 
     * @return string|null - return category title or null if it is not exist
     */
    public function predict (array $sample): ?string
    {
        for ($i = 1, $l = sizeof ($sample); $i <= $l; ++$i)
            foreach ($this->ngrams ($sample, $i) as $feature)
                foreach ($this->patterns as $name => $patterns)
                    if (in_array ($feature, $patterns))
                        return (string) $name;

        return null;
    }

    /**
     * Get array of patterns
     * 
     * Can be used in Tree constructor
     * 
     * @return array
     */
    public function getPatterns (): array
    {
        return $this->patterns;
    }

    /**
     * Get array of samples
     * 
     * @return array
     */
    public function getSamples (): array
    {
        $samples = [];

        for ($i = 0, $l = sizeof ($this->dataset); $i < $l; ++$i)
            $samples[$this->labels[$i]][] = $this->dataset[$i];

        return $samples;
    }

    /**
     * Get list of n-grams for given items
     * 
     * @param array $items - list of scalar items
     * [@param int $n = 1] - size of n-grams
     * 
     * @return array
     * 
     * @example
     * 
     * $this->ngrams ([1, 2, 3], 2); // [[1, 2], [2, 3]]
     */
    protected static function ngrams (array $items, int $n = 1): array
    {
        $ngrams = [];
        $items  = array_values ($items);

        for ($i = 0, $l = sizeof ($items) - $n + 1; $i < $l; ++$i)
            $ngrams[] = join (array_slice ($items, $i, $n));

        return $ngrams;
    }

    /**
     * Identify key features for given items
     * 
     * I also will not explain how it works
     * 
     * @param array $items
     * @param array $features
     * 
     * @return array
     */
    protected function identFeatures (array $items, array $features): array
    {
        foreach ($features as $feature)
        {
            $used = 0;
            $usedIn = null;

            foreach ($items as $name => $ngrams)
                if (in_array ($feature, $ngrams))
                {
                    $usedIn = $name;

                    if (++$used == 2)
                        break;
                }
            
            if ($used == 1)
            {
                $features = array_diff ($features, $items[$usedIn]);
                unset ($items[$usedIn]);

                return array_merge ([
                    $usedIn => $feature
                ], $this->identFeatures ($items, $features));
            }
        }

        return [];
    }
}
