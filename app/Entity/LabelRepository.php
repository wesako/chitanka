<?php namespace App\Entity;

/**
 *
 */
class LabelRepository extends EntityRepository {
	/**
	 * @param string $slug
	 */
	public function findBySlug($slug) {
		return $this->findOneBy(['slug' => $slug]);
	}

	public function getAll() {
		$labelResult = $this->getQueryBuilder()
			->addSelect('IDENTITY(e.parent) AS parent')
			->orderBy('e.name')
			->getQuery()
			->getArrayResult();
		foreach ($labelResult as $k => $row) {
			$labelResult[$k] += $row[0];
			unset($labelResult[$k][0]);
		}
		return $labelResult;
	}

	/**
	 * @return array
	 */
	public function getAllAsTree() {
		return $this->convertArrayToTree($this->getAll());
	}

	protected function convertArrayToTree($labels) {
		$labelsById = [];
		foreach ($labels as $i => $label) {
			$labelsById[ $label['id'] ] =& $labels[$i];
		}

		foreach ($labels as $i => $label) {
			if ($label['parent']) {
				$labelsById[$label['parent']]['children'][] =& $labels[$i];
			}
		}

		return $labels;
	}

	public function getNames() {
		return $this->_em->createQueryBuilder()
			->from($this->getEntityName(), 'l')->select('l.id, l.name')
			->getQuery()->getResult('key_value');
	}

	/**
	 * @param string $name
	 */
	public function getByNames($name) {
		return $this->getQueryBuilder()
			->where('e.name LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery()
			->getArrayResult();
	}

}
