<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Logger;

use Monolog\Formatter\LineFormatter;

class TreviPayFormatter extends LineFormatter
{
    /**
     * {@inheritdoc}
     */
    public function stringify($data) : string
    {
        if ($data === null || is_bool($data)) {
            // phpcs:ignore
            return print_r($data, true);
        }

        if (is_scalar($data)) {
            return (string) $data;
        }

        // phpcs:ignore
        return print_r($data, true);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
        $output = parent::format($record);
        if (is_string($output)) {
            $output = $this->removeEmptyLines($output);
            $output = trim($output) . PHP_EOL;
        }

        return $output;
    }

    private function removeEmptyLines(string $output): string
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", PHP_EOL, $output);
    }
}
