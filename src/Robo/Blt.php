<?php

namespace Acquia\Blt\Robo;

use Acquia\Blt\Robo\LocalEnvironment\LocalEnvironment;
use Acquia\Blt\Robo\LocalEnvironment\LocalEnvironmentInterface;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Config;
use Robo\Robo;
use Robo\Runner as RoboRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Blt implements ContainerAwareInterface, LoggerAwareInterface {

  use ConfigAwareTrait;
  use ContainerAwareTrait;
  use LoggerAwareTrait;

  /**
   * @var \Robo\Runner
   */
  private $runner;
  /**
   * @var string[]
   */
  private $commands = [];

  /**
   * Object constructor
   *
   * @param \Robo\Config $config
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  public function __construct(Config $config, InputInterface $input = null, OutputInterface $output = null)
  {
    $this->setConfig($config);
    $application = new Application('BLT', $config->get('version'));
    $container = Robo::createDefaultContainer($input, $output, $application, $config);
    $this->setContainer($container);
    $this->addDefaultArgumentsAndOptions($application);
    $this->configureContainer();
    $this->addBuiltInCommandsAndHooks();
    // $this->addPluginsCommandsAndHooks();
    $this->runner = new RoboRunner();
    $this->runner->setContainer($container);
    $this->setLogger($container->get('logger'));
    // date_default_timezone_set($config->get('time_zone'));
  }

  /**
   * Add the commands and hooks which are shipped with core Terminus
   */
  private function addBuiltInCommandsAndHooks()
  {
    $commands = $this->getCommands([
      'path' => __DIR__ . '/Commands',
      'namespace' => 'Acquia\Blt\Robo\Commands',
    ]);
    $hooks = $this->getHooks([
      'path' => __DIR__ . '/Hooks',
      'namespace' => 'Acquia\Blt\Robo\Hooks',
    ]);
    $this->commands = array_merge($commands, $hooks);
  }

  /**
   * Discovers command classes using CommandFileDiscovery
   *
   * @param string[] $options Elements as follow
   *        string path      The full path to the directory to search for commands
   *        string namespace The full namespace associated with given the command directory
   * @return TerminusCommand[] An array of TerminusCommand instances
   */
  private function getCommands(array $options = ['path' => null, 'namespace' => null,])
  {
    $discovery = new CommandFileDiscovery();
    $discovery->setSearchPattern('*Command.php')->setSearchLocations([]);
    return $discovery->discover($options['path'], $options['namespace']);
  }

  /**
   * Discovers hooks using CommandFileDiscovery
   *
   * @param string[] $options Elements as follow
   *        string path      The full path to the directory to search for commands
   *        string namespace The full namespace associated with given the command directory
   * @return TerminusCommand[] An array of TerminusCommand instances
   */
  private function getHooks(array $options = ['path' => null, 'namespace' => null,])
  {
    $discovery = new CommandFileDiscovery();
    $discovery->setSearchPattern('*Hook.php')->setSearchLocations([]);
    return $discovery->discover($options['path'], $options['namespace']);
  }

  /**
   * Add any global arguments or options that apply to all commands.
   *
   * @param \Symfony\Component\Console\Application $app
   */
  private function addDefaultArgumentsAndOptions(Application $app)
  {
    $app->getDefinition()->addOption(new InputOption('--yes', '-y', InputOption::VALUE_NONE, 'Answer all confirmations with "yes"'));
  }

  /**
   * Register the necessary classes for Terminus
   */
  private function configureContainer()
  {
    $container = $this->getContainer();

    $local_environment = new LocalEnvironment();
    $container->share('local_environment', $local_environment);
    $container->inflector(LocalEnvironmentInterface::class)
      ->invokeMethod('setLocalEnvironment', ['local_environment']);

    // Tell the command loader to only allow command functions that have a name/alias.
    $factory = $container->get('commandFactory');
    $factory->setIncludeAllPublicMethods(false);
  }

  /**
   * Runs the instantiated Terminus application
   *
   * @param InputInterface  $input  An input object to run the application with
   * @param OutputInterface $output An output object to run the application with
   * @return integer $status_code The exiting status code of the application
   */
  public function run(InputInterface $input, OutputInterface $output)
  {
    $status_code = $this->runner->run($input, $output, null, $this->commands);

    return $status_code;
  }
}
