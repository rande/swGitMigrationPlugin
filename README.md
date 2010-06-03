# swGitMigrationPlugin

This plugin is useful for people who have already migrate a symfony plugin from subversion to a git repository, but still use subversion for controlling the source of the main project.

_So the plugin is maintained on a git repository and the project is maintained on a subversion project._

  - **WARNING 1 : This plugin can delete all your files, and destroy your repository.**
  - **WARNING 2 : There is no confirmation message, once you start the command, just watch ...**

## Requirements 

 - You must have git and svn executable available on your plateform
 - You must have a project under subversion (otherwise just use git submodule)

## Usage

    ./symfony sw:git-plugin-migration swCombinePlugin git@github.com:rande/swCombinePlugin.git


### Usecase 1 : plugin defined as an external

swGitMigrationPlugin will

  - remove the externals (commit the change)
  - remove the plugin folder
  - create a new plugin folder
  - create a file .sw_git_migration with the git repository information (for later reuse)
  - clone the git repository into a temporary folder
  - copy files from the temporary folder to the plugin directory
  - set svn:ignore on .git folder
  - add files into the subversion repository
  - commit svn change into the subversion repository


### Usecase 2 : plugin already in subversion (ie, plugin install manually

swGitMigrationPlugin will

  - create a file .sw_git_migration with the git repository information (for later reuse)
  - clone the git repository into a temporary folder
  - copy files from the temporary folder to the plugin directory
  - set svn:ignore on .git folder
  - add files into the subversion repository
  - commit svn change into the subversion repository

### Usecase 3 : I have the .sw_git_migration file, next ?

Start the command

    ./symfony sw:git-restore [--plugins=swCombinePlugin,swCombinePlugin2]

The command will restore git repository inside each plugins folders.

## Great, but why ?

  - git is a cool way to share code and improve project
  - I am migrating my codebase to git, so migration get easier 
  - I can tweak plugin to match project requirements and still have a nice way to get update from git
  - Symfony 2 and the next plugin generation are going to be based on git, so it never to late to get into *good stuff*

## Find a bug ?

  - just open an issue here : http://github.com/rande/swGitMigrationPlugin/issues
  - fork it and code the patch

