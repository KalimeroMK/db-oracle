<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Builder;

use Yiisoft\Db\Expression\Value\UuidValue;
use Yiisoft\Db\Helper\DbUuidHelper;
use Yiisoft\Db\Oracle\Builder\UuidValueBuilder;
use Yiisoft\Db\Oracle\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

use function strtolower;

/**
 * @group oracle
 */
final class UuidValueBuilderTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    private const UUID = '738146be-87b1-49f2-9913-36142fb6fcbe';

    public function testBuildEmitsHexToRawLiteral(): void
    {
        $db = $this->getSharedConnection();
        $builder = new UuidValueBuilder($db->getQueryBuilder());

        $params = [];
        $result = $builder->build(new UuidValue(self::UUID), $params);

        $this->assertSame("HEXTORAW('738146be87b149f2991336142fb6fcbe')", $result);
        $this->assertSame([], $params);
    }

    public function testInsertAndSelectUuid(): void
    {
        $db = $this->getSharedConnection();

        $this->dropTable('uuid_value');
        $this->executeStatements('CREATE TABLE [[uuid_value]] ([[id]] raw(16) NOT NULL)');

        $db->createCommand()->insert('uuid_value', ['id' => new UuidValue(self::UUID)])->execute();

        // Depending on the Oracle version a `raw` column reads back as 16 raw bytes or as 32 hexadecimal characters,
        // uppercase in the latter case. `DbUuidHelper::toUuid()` accepts both but keeps the case it was given.
        $stored = $db->createCommand('SELECT [[id]] FROM [[uuid_value]]')->queryScalar();

        $this->assertSame(self::UUID, strtolower(DbUuidHelper::toUuid($stored)));

        $this->dropTable('uuid_value');
    }
}
