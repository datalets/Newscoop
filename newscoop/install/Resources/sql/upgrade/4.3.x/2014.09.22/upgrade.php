<?php

$newscoopDir = realpath(dirname(__FILE__).'/../../../../../../').'/';
$rootDir = realpath($newscoopDir.'../').'/';
$currentDir = dirname(__FILE__) .'/';
$diffFile = 'delete_diff.txt';

require_once $newscoopDir.'vendor/autoload.php';

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

// Remove files
$finder = new Finder();
$filesystem = new Filesystem();
$finder->files()->in($currentDir)->name($diffFile);

$contents = null;
foreach ($finder as $file) {
    $contents = $file->getContents();
}

$filesToBeDeleted =  array_filter(preg_split('/\r\n|\n|\r/', $contents));
$folderToBeChecked = array();

if (count($filesToBeDeleted) > 0) {

    foreach ($filesToBeDeleted as $file) {

        if (strpos($file, 'newscoop/') === 0) {
            $fullPath = $newscoopDir.substr($file, 9);
        } else {
            $fullPath = $rootDir.$file;
        }

        if (is_file($fullPath) && is_readable($fullPath)) {

            $filesystem->remove(array($fullPath));

            if (strpos($file, '/') !== false && strpos($file, 'newscoop/admin-files/lang') === false) {
                $folderToBeChecked[] = dirname($fullPath);
            }
        }
    }
}

$folderToBeChecked = array_unique($folderToBeChecked);
arsort($folderToBeChecked);

// Add extra directories to remove recusively
$folderToBeChecked[] = $newscoopDir .'admin-files/lang';
$folderToBeChecked[] = $newscoopDir .'example';
$folderToBeChecked[] = $newscoopDir .'extensions/google-gadgets';
$folderToBeChecked[] = $newscoopDir .'install/cron_jobs';
$folderToBeChecked[] = $newscoopDir .'install/sample_data';

$folderToBeChecked[] = $rootDir .'cookbooks';
$folderToBeChecked[] = $rootDir .'dependencies';
$folderToBeChecked[] = $rootDir .'scripts';

foreach ($folderToBeChecked as $folder) {

    if (is_dir($folder)) {

        $finder = new Finder();
        $finder->in($folder);

         if (iterator_count($finder->files()) == 0) {

            try {
                // Remove subdirectories
                foreach ($finder->directories()->sortByName() as $subFolder) {
                    if (is_dir($subFolder)) {
                        $filesystem->remove(array($subFolder));
                    }
                }
            } catch (\Exception $e) {
                continue;
            }

            // Remove parent directory
            $filesystem->remove(array($folder));
        }
    }
}

// Make system calls
system("php ${newscoopDir}application/console assets:install ${newscoopDir}public/ > /dev/null");

// Update composer
system("php ${newscoopDir}composer.phar -q --working-dir=\"${newscoopDir}\" dump-autoload --optimize");