<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace OCA\Workspace\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version0000Date20210615125333 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {

		$schema = $schemaClosure();

		if ( $schema->hasTable('work_spaces') ) {
			$schema->dropTable('work_spaces');
		}

		$table = $schema->createTable('work_spaces');

		$table->addColumn('space_id', 'bigint', [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 6
		]);

		$table->addColumn('groupfolder_id', 'bigint', [
			'autoincrement' => false,
			'notnull' => true,
			'length' => 6
		]);

		$table->addColumn('color_code', 'string', [
			'notnull' => false,
			'length' => 128,
			'default' => null
		]);

		$table->addColumn('space_name', 'string', [
			'notnull' => true,
			'length' => 128,
		]);
		
		$table->changeColumn('space_name', [
			'notnull' => true,
			'length' => 4000
		]);

		$table->setPrimaryKey([
			'space_id',
			'groupfolder_id',
		]);

		$table->addForeignKeyConstraint($schema->getTable('group_folders'), ['space_name'], ['mount_point'], ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'], 'fk_gi_sn_work_spaces');
		$table->addForeignKeyConstraint($schema->getTable('group_folders'), ['groupfolder_id'], ['folder_id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'], 'fk_gi_sn_work_spaces');
		// $table->addForeignKeyConstraint($schema->getTable('group_folders'), ['groupfolder_id', 'space_name'], ['folder_id', 'mount_point'], [], 'fk_groupfolder_id_work_spaces');

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
