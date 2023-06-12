<?php

namespace Lullabot\DrainpipeDev\ConvertToJUnitXML\Converters\TwigLinter;

use TijsVerkoyen\ConvertToJUnitXML\Converters\ConverterInterface;
use TijsVerkoyen\ConvertToJUnitXML\Converters\Exceptions\InvalidInputException;
use TijsVerkoyen\ConvertToJUnitXML\JUnit\Failure;
use TijsVerkoyen\ConvertToJUnitXML\JUnit\JUnit;
use TijsVerkoyen\ConvertToJUnitXML\JUnit\TestCase;
use TijsVerkoyen\ConvertToJUnitXML\JUnit\TestSuite;

class TwigLinter implements ConverterInterface
{
    public function convert(string $input): JUnit
    {
        $data = json_decode($input);
        print_r($input);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw InvalidInputException::invalidJSON();
        }

        $files = [];
        foreach ($data as $file) {
            $files[$file->file][] = $file;
        }

        $jUnit = new JUnit();
        $testSuite = new TestSuite('twig-linter');

        foreach ($files as $file) {
            $testCase = new TestCase(
                sprintf(
                    '%1$s',
                    $file[0]->file
                )
            );

            foreach ($file as $report) {
                if (!$report->valid) {
                    $testCase->addFailure(
                        new Failure(
                            'error',
                            sprintf(
                                '%1$s in %2$s on line: %3$s, column: %4$s.',
                                $report->message,
                                $report->file,
                                $report->line,
                                0
                            ),
                            sprintf(
                                '%1$s in %2$s on line: %3$s, column: %4$s.',
                                $report->message,
                                $report->file,
                                $report->line,
                                0
                            )
                        )
                    );
                }
            }
            $testSuite->addTestCase($testCase);
        }
        $jUnit->addTestSuite($testSuite);

        return $jUnit;
    }
}
