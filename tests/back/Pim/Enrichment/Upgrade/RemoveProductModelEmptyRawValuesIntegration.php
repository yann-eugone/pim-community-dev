<?php

use Akeneo\Test\Integration\TestCase;
use Akeneo\Tool\Component\Console\CommandLauncher;
use Doctrine\DBAL\Connection;

/**
 * This class will be removed after 4.0 version
 */
class RemoveProductModelEmptyRawValuesIntegration extends TestCase
{
    protected function getConfiguration()
    {
        $this->catalog->useTechnicalSqlCatalog();
    }

    public function testItRemovesAllEmptyValues()
    {
        $this->getConnection()->executeQuery('DELETE FROM pim_catalog_product_model');
        $sql = <<<SQL
INSERT INTO pim_catalog_product_model VALUES
    (NULL, NULL, 10, 'pm1', '{"name": {"<all_channels>": {"<all_locales>": ""}}}', NOW(), NOW(), 0, 0, 0, 0),
    (NULL, NULL, 10, 'pm2', '{"name": {"<all_channels>": {"<all_locales>": []}}}', NOW(), NOW(), 0, 0, 0, 0),
    (NULL, NULL, 10, 'pm3', '{"name": {"<all_channels>": {"<all_locales>": [""]}}}', NOW(), NOW(), 0, 0, 0, 0),
    (NULL, NULL, 10, 'pm4', '{"name": {"<all_channels>": {"<all_locales>": null}}}', NOW(), NOW(), 0, 0, 0, 0),
    (NULL, NULL, 10, 'pm5', '{"name": {"<all_channels>": {"<all_locales>": ""}}, "foo": {"<all_channels>": {"<all_locales>": "bar"}}}', NOW(), NOW(), 0, 0, 0, 0),
    (NULL, NULL, 10, 'pm6', '{"name": {"<all_channels>": {"fr_FR": "", "en_US": "bar"}}}', NOW(), NOW(), 0, 0, 0, 0),
    (NULL, NULL, 10, 'pm7', '{"name": {"ecommerce": {"<all_locales>": ""}, "mobile": {"<all_locales>": "bar"}}}', NOW(), NOW(), 0, 0, 0, 0)
SQL;

        $this->getConnection()->executeQuery($sql);

        $this->reExecuteMigration('_4_0_20190917080512_remove_product_model_empty_raw_values');

        $this->assertProductModelRawValuesEquals('pm1', '{}');
        $this->assertProductModelRawValuesEquals('pm2', '{}');
        $this->assertProductModelRawValuesEquals('pm3', '{}');
        $this->assertProductModelRawValuesEquals('pm4', '{}');
        $this->assertProductModelRawValuesEquals('pm5', '{"foo": {"<all_channels>": {"<all_locales>": "bar"}}}');
        $this->assertProductModelRawValuesEquals('pm6', '{"name": {"<all_channels>": {"en_US": "bar"}}}');
        $this->assertProductModelRawValuesEquals('pm7', '{"name": {"mobile": {"<all_locales>": "bar"}}}');
    }

    private function getCommandLauncher(): CommandLauncher
    {
        return $this->get('pim_catalog.command_launcher');
    }

    private function getConnection(): Connection
    {
        return $this->get('database_connection');
    }

    private function assertProductModelRawValuesEquals(string $productModelCode, string $expectedRawValues)
    {
        $result = $this->getConnection()->fetchArray(
            'SELECT raw_values FROM pim_catalog_product_model WHERE code=:code',
            ['code' => $productModelCode]
        );
        $this->assertEquals($expectedRawValues, $result[0]);
    }

    private function reExecuteMigration(string $migrationLabel)
    {
        $resultDown = $this->getCommandLauncher()->executeForeground(sprintf('doctrine:migrations:execute %s --down -n', $migrationLabel));
        $this->assertEquals(0, $resultDown->getCommandStatus());

        $resultUp = $this->getCommandLauncher()->executeForeground(sprintf('doctrine:migrations:execute %s --up -n', $migrationLabel));
        $this->assertEquals(0, $resultUp->getCommandStatus());
    }
}
