<?php
/**
 * @file
 * Contains \Larowlan\Tl\Tests\TlTestBase.
 */

namespace Larowlan\Tl\Tests;

use Larowlan\Tl\Application;
use Larowlan\Tl\Connector\Connector;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Tests\Output\TestOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines a base test class.
 */
abstract class TlTestBase extends \PHPUnit_Framework_TestCase {

  /**
   * Test application.
   *
   * @var \Larowlan\Tl\Application
   */
  protected $application;

  /**
   * Install schema automatically.
   *
   * @var bool
   */
  protected $installSchema = TRUE;

  /**
   * Test container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $container = new ContainerBuilder();
    $bin_dir = dirname(__DIR__) . '/';
    $loader = new YamlFileLoader($container, new FileLocator($bin_dir));
    $loader->load('services.yml');
    $test_directory = sys_get_temp_dir() . '/' . uniqid('tl');
    mkdir($test_directory);
    file_put_contents($test_directory . '/.tl.yml', Yaml::dump(['url' => 'http://example.com']));
    $container->setParameter('directory', $test_directory);
    $mock_connector = $this->getMock(Connector::class);
    $container->set('connector', $mock_connector);
    $configuration_processor = $this->getMock(Processor::class);
    $configuration_processor->expects($this->any())
      ->method('processConfiguration')
      ->willReturn([]);
    $container->set('config.processor', $configuration_processor);
    $this->container = $container;
    $this->application = new Application('Time logger', 'testing', $container);
    if ($this->installSchema) {
      $install = $container->get('app.command.install');
      $install->setApplication($this->application);
      $input = new ArrayInput(['command' => 'install']);
      $output = $this->getMock(OutputInterface::class);
      $install->run($input, $output);
    }
  }

  protected function tearDown() {
    parent::tearDown(); // TODO: Change the autogenerated stub
  }


  /**
   * Gets the mock connector.
   *
   * @return \Larowlan\Tl\Connector\Connector|\PHPUnit_Framework_MockObject_MockObject $connector
   *   The Mock connector.
   */
  protected function getMockConnector() {
    return $this->container->get('connector');
  }

  /**
   * Gets the db repository.
   *
   * @return \Larowlan\Tl\Repository\Repository
   *   The DB repository.
   */
  protected function getRepository() {
    return $this->container->get('repository');
  }

  /**
   * Executes a console command.
   *
   * @param string $name
   *   Command name.
   * @param array $input
   *   Command input. Pass arguments as named keys, pass options as named keys
   *   with a -- prefix.
   *
   * @code@
   *   $this->executeCommand('tag', [
   *     'slot_id' => 12345,
   *     '--retag' => TRUE
   *   ]);
   * @endcode@
   *
   * @return \Symfony\Component\Console\Tester\CommandTester
   *   Command tester. Use ::getDisplay() to return the output.
   */
  protected function executeCommand($name, array $input = []) {
    $command = $this->container->get('app.command.' . $name);
    $command->setApplication($this->application);
    $command_tester = new CommandTester($command);
    $command_tester->execute(['command' => $command->getName()] +  $input);
    return $command_tester;
  }

}
