<?php

/*
 * This file is part of the swGitMigrationPlugin package.
 *
 * (c) 2010 Thomas Rabaix <thomas.rabaix@soleoweb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
class swGitRestoreTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
    ));

    $this->addOptions(array(
      new sfCommandOption('git', null, sfCommandOption::PARAMETER_OPTIONAL, 'The git executable', 'git'),
      new sfCommandOption('plugins', null, sfCommandOption::PARAMETER_OPTIONAL, 'The git executable', 'all'),
      // add your own options here
    ));

    $this->namespace        = 'sw';
    $this->name             = 'git-restore';
    $this->briefDescription = 'restore a git repository by reading a .sw_git_migration';
    $this->detailedDescription = <<<EOF
The [swGitPluginMigration|INFO] task does things.
Call it with:

  [php symfony swGitPluginMigration|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $plugins = explode(',', $options['plugins']);
    
    foreach(glob(sfConfig::get('sf_plugins_dir').'/*', GLOB_ONLYDIR) as $plugin_path)
    {
      $plugin = basename($plugin_path);
      
      if($options['plugins'] != 'all' && !in_array($plugin, $plugins))
      {
        continue;
      }

      if(is_dir(sfConfig::get('sf_plugins_dir').'/'.$plugin.'/.git'))
      {
        $this->logSection($plugin, 'plugin folder already under git management');
        continue;
      }
            
      $file = sfConfig::get('sf_plugins_dir').'/'.$plugin.'/.sw_git_migration';
      
      if(!is_file($file) && $options['plugins'] != 'all')
      {
        $this->logSection($plugin, 'no .sw_git_migration file');
        continue;
      }
      
      $git_repository = trim(file_get_contents($file));

      $this->logSection($plugin, 'starting restore ...');
      
      // remove current directory content
      $this->getFileSystem()->remove(
        sfFinder::type('any')
          ->ignore_version_control(false)
          ->in(sprintf('%s/.sw_git_migration_plugin', sfConfig::get('sf_plugins_dir')))
      );

      // run the task to deploy a git repository over a svn repo
      $this->logSection($plugin, 'clone the repository');
      $cmd = sprintf('%s clone %s %s/.sw_git_migration_plugin', $options['git'], $git_repository, sfConfig::get('sf_plugins_dir'));
      $this->getFilesystem()->execute($cmd);

      $this->logSection($plugin, 'copy the content into the');

      $this->getFileSystem()->mirror(
        sprintf('%s/.sw_git_migration_plugin', sfConfig::get('sf_plugins_dir')),
        sfConfig::get('sf_plugins_dir').'/'.$plugin,
        sfFinder::type('any')->ignore_version_control(false),
        array('overwrite' => true)
      );

      $this->getFileSystem()->remove(
        sfFinder::type('any')
          ->ignore_version_control(false)
          ->in(sprintf('%s/.sw_git_migration_plugin', sfConfig::get('sf_plugins_dir')))
      );
    }

  }
}
