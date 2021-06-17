<?php

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

		$table->addForeignKeyConstraint($schema->getTable('group_folders'), ['space_name'], ['mount_point'], [], 'fk_gi_sn_work_spaces');
		$table->addForeignKeyConstraint($schema->getTable('group_folders'), ['groupfolder_id'], ['folder_id'], [], 'fk_gi_sn_work_spaces');
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
