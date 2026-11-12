<?php

/**
 * @copyright Copyright (c) 2026 Arawa
 *
 * @author 2026 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

class Version4005Date20260923155342 extends SimpleMigrationStep {
	public function name(): string {
		return 'Remove the index on work_spaces.space_name';
	}

	public function description(): string {
		return 'Drops the index on work_spaces.space_name, which exceeds the MySQL key length limit (#585), and restores the column to 4000 characters where it was shrunk as a workaround';
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('work_spaces')) {
			return null;
		}

		$table = $schema->getTable('work_spaces');
		$changed = false;

		foreach ($table->getIndexes() as $index) {
			if (!$index->isPrimary() && $index->getColumns() === ['space_name']) {
				$table->dropIndex($index->getName());
				$changed = true;
			}
		}

		$column = $table->getColumn('space_name');
		if ($column->getLength() < 4000) {
			$column->setLength(4000);
			$changed = true;
		}

		return $changed ? $schema : null;
	}
}
