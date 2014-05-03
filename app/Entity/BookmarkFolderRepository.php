<?php namespace App\Entity;

/**
 *
 */
class BookmarkFolderRepository extends EntityRepository {

	/**
	 * @param User $user
	 * @param string $folderSlug
	 * @param string $folderName
	 */
	public function getOrCreateForUser($user, $folderSlug, $folderName = '') {
		$folder = $this->findOneBy(array('slug' => $folderSlug, 'user' => $user->getId()));
		if ( ! $folder) {
			$folder = new BookmarkFolder;
			$folder->setSlug($folderSlug);
			$folder->setName($folderName ? $folderName : $folderSlug);
			$folder->setUser($user);
		}

		return $folder;
	}

}