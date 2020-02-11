<?php

namespace Ministra\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
class Version1535720028 extends \Doctrine\DBAL\Migrations\AbstractMigration
{
    public function up(\Doctrine\DBAL\Schema\Schema $schema)
    {
        if ($schema->getTable('users')->hasColumn('settings_password')) {
            $this->addSql('ALTER TABLE `users` DROP COLUMN `settings_password`');
        }
        $this->addSql(<<<EOL
--
ALTER TABLE `users`
ADD COLUMN `settings_password` VARCHAR(60) NOT NULL DEFAULT '0000';
EOL
);
    }
    public function down(\Doctrine\DBAL\Schema\Schema $schema)
    {
        $this->addSql(<<<EOL
ALTER TABLE `users` DROP COLUMN `settings_password`;
--
EOL
);
    }
}
