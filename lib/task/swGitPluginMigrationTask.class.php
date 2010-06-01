<?php
/*
 * This file is part of the swGitMigrationPlugin package.
 *
 * (c) 2010 Thomas Rabaix <thomas.rabaix@soleoweb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class swGitPluginMigrationTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('plugin', sfCommandArgument::REQUIRED, 'The plugin name'),
      new sfCommandArgument('git-repository', sfCommandArgument::REQUIRED, 'The git\'s url repository'),
    ));

    $this->addOptions(array(
      new sfCommandOption('svn', null, sfCommandOption::PARAMETER_OPTIONAL, 'The svn executable', 'svn'),
      new sfCommandOption('git', null, sfCommandOption::PARAMETER_OPTIONAL, 'The git executable', 'git'),
      new sfCommandOption('svn-commit-prefix', null, sfCommandOption::PARAMETER_OPTIONAL, 'The svn commit prefix', '[swGitMigration] %s'),
      // add your own options here
    ));

    $this->namespace        = 'sw';
    $this->name             = 'git-plugin-migration';
    $this->briefDescription = '';
    $this->detailedDescription = <<<EOF
The [swGitPluginMigration|INFO] task does things.
Call it with:

  [php symfony swGitPluginMigration|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $plugin = $arguments['plugin'];
    
    if(is_dir(sfConfig::get('sf_plugins_dir').'/'.$plugin.'/.git'))
    {
      throw new RuntimeException('The plugin directory is already managed by git');
    }
    
    // check if the plugin is set as externals or installed
    $cmd = sprintf('%s pg svn:externals %s', $options['svn'], sfConfig::get('sf_plugins_dir'));
    list($output, $err) = $this->getFilesystem()->execute($cmd);
    
    $external_offset  = false;
    $externals        = explode("\n", $output);
    foreach($externals as $pos => $external)
    {
      if(trim($external) == "")
      {
        continue;
      }

      if(strpos($external, $plugin." ") !== false) 
      {
        $external_offset = $pos;
        break;
      }
    }
    
    if($external_offset !== false)
    {
      $this->logSection('svn', 'the plugin is defined as an external');
      
      unset($externals[$external_offset]);
      $new_externals = implode("\n", $externals);
      $this->logBlock(
        'The command is going to update the svn:externals of plugins to:'."\n\n".
        $new_externals, 'INFO'
      );
      
      // update externals
      $this->getFileSystem()->touch('git_migration.tmp');
      file_put_contents('git_migration.tmp', $new_externals);
      $cmd = sprintf('%s ps svn:externals2 -F %s %s', $options['svn'], 'git_migration.tmp', sfConfig::get('sf_plugins_dir'));
      
      $this->getFilesystem()->execute($cmd);
      $this->getFilesystem()->remove('git_migration.tmp');
      
      // commit the change to svn
      $this->logSection('svn', 'commit the change');
      $cmd = sprintf('%s ci --non-recursive %s -m "%s" ', 
        $options['svn'], 
        sfConfig::get('sf_plugins_dir'), 
        sprintf($options['svn-commit-prefix'], 'update externals to remove the ' . $plugin . ' reference')
      );

      // $this->getFilesystem()->execute($cmd);    
      
      // remove the plugin folder
      $this->logSection('svn', 'delete current file content');
      // $this->getFilesystem()->remove(sfConfig::get('sf_plugins_dir').'/'.$plugin);
      
      // create a new clean folder
      $this->getFilesystem()->mkdirs(sfConfig::get('sf_plugins_dir').'/'.$plugin);

      $cmd = sprintf('%s add --non-recursive %s', $options['svn'], sfConfig::get('sf_plugins_dir').'/'.$plugin);
      $this->getFilesystem()->execute($cmd);
    }
    else
    {
      $this->logSection('svn', 'the plugin has been installed with the symfony installer');
    }
    
    // create repo information
    $this->logSection('migration', 'create .sw_git_migration file');
    $file = sfConfig::get('sf_plugins_dir').'/'.$plugin.'/.sw_git_migration';
    $this->getFilesystem()->touch($file);
    file_put_contents($file, $arguments['git-repository']);


    $task = new swGitRestoreTask($this->dispatcher, $this->formatter);
    $task->setCommandApplication($this->commandApplication);
    $task->setConfiguration($this->configuration);
    $ret = $task->run(array(), array('plugins' => $plugin));

    // 
    if(!is_file(sfConfig::get('sf_plugins_dir').'/'.$plugin.'/.gitignore'))
    {
      $this->getFileSystem()->touch(sfConfig::get('sf_plugins_dir').'/'.$plugin.'/.gitignore');
    }
    
    $content = trim(file_get_contents(sfConfig::get('sf_plugins_dir').'/'.$plugin.'/.gitignore'));
    if(strpos($content, '.svn') === false)
    {
      $content .= "\n.svn";
    }
    
    file_put_contents(sfConfig::get('sf_plugins_dir').'/'.$plugin.'/.gitignore', $content);
    
    if($external_offset !== false)
    {
      $this->logSection('svn','put back files to the svn directory');
      $cmd = sprintf('%s add %s/*', $options['svn'], sfConfig::get('sf_plugins_dir').'/'.$plugin);
      $this->getFilesystem()->execute($cmd);
      
      $this->logSection('svn','commit change into svn repository');
      $cmd = sprintf('%s ci %s -m "%s" ', 
        $options['svn'], 
        sfConfig::get('sf_plugins_dir').'/'.$plugin, 
        sprintf($options['svn-commit-prefix'], 'add ' . $plugin . ' files')
      );
        
      $this->getFilesystem()->execute($cmd);
    }
  }
}
