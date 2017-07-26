Feature: Install command
  Download libraries and prepare autoload header.

  Scenario: First installation
    Given there is package which requires:
      | package     | version |
      | hello/world | *       |
    And local repository contains packages:
      | package     | version |
      | hello/world | 1.0.0   |
    When I run "deplink install --no-progress -v"
    Then the console output should contains:
      """
      Retrieving installed dependencies... Skipped
      Resolving dependencies tree... OK
      Dependencies: 1 install, 0 updates, 0 removals
        - Installing hello/world (v1.0.0)
      Writing lock file... OK
      Generating autoload header... OK
      """
    And command should exit with status code 0
    And the "deplinks/autoload.h" file should contains:
      """
      #pragma once

      #include "hello/world/include/main.h"
      """

  Scenario: The deplink.json file not exists
    Given file "deplink.json" not exists
    When I run "deplink install"
    And the console output should contains "Working directory is not the deplink project (check path or initialize project usign `deplink init` command)"
    And command should exit with status code 1

  Scenario: Invalid deplink.json structure
    Given there is "deplink.json" file with contents:
      """
      { "missingColon" 5 }
      """
    When I run "deplink install"
    And the console output should contains "Invalid json format of the deplink.json file"
    And command should exit with status code 1

  Scenario: Install related dependencies
    Given there is package which requires:
      | package      | version |
      | hello/lipsum | *       |
    And the "hello/lipsum" package requires:
      | package     | version |
      | hello/world | *       |
    And local repository contains packages:
      | package      | version |
      | hello/world  | 1.0.0   |
      | hello/lipsum | 1.0.0   |
    When I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Retrieving installed dependencies... OK
      Resolving dependencies tree... OK
      Dependencies: 2 install, 0 updates, 0 removals
        - Installing hello/lipsum (v1.0.0)
        - Installing hello/world (v1.0.0)
      Writing lock file... OK
      Generating autoload header... OK
      """
    And command should exit with status code 0
    And the "deplinks/autoload.h" file should contains:
      """
      #pragma once

      #include "hello/world/include/main.h"
      #include "hello/lipsum/include/main.h"
      """

  Scenario: Install only root dev dependencies
    Given there is package which requires:
      | package    | version | isDev |
      | basic/log  | *       | true  |
      | basic/math | *       | false |
    And the "basic/math" package requires:
      | package    | version | isDev |
      | basic/unit | *       | true  |
    And local repository contains packages:
      | package    | version |
      | basiclog   | 1.0.0   |
      | basic/unit | 1.0.0   |
      | basic/math | 1.0.0   |
    When I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Retrieving installed dependencies... OK
      Resolving dependencies tree... OK
      Dependencies: 2 install, 0 updates, 0 removals
        - Installing basic/log (v1.0.0)
        - Installing basic/math (v1.0.0)
      Writing lock file... OK
      Generating autoload header... OK
      """
    And command should exit with status code 0

  Scenario: Skip installation of dev dependencies
    Given there is package which requires:
      | package   | version | isDev |
      | basic/log | *       | true  |
    When I run "deplink install --no-progress --no-dev"
    Then the console output should contains:
      """
      Dependencies: 0 install, 0 updates, 0 removals
      """
    And command should exit with status code 0

  Scenario: Detect dependencies loop
    Given there is package which requires:
      | package    | version |
      | loops/libA | *       |
    And the "loops/libA" package requires:
      | package    | version |
      | loops/libB | *       |
    And the "loops/libB" package requires:
      | package    | version |
      | loops/libA | *       |
    When I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Dependencies loop detected: loops/libA -> loops/libB ... loops/libB -> loops/libA
      """
    And command should exit with status code 1

  Scenario: Detect mismatch between linking type
    Given there is package which requires:
      | package              | version   |
      | linking/any          | *:dynamic |
      | linking/linkToStatic | *         |
    And the "linking/linkToStatic" package requires:
      | package     | version  |
      | linking/any | *:static |
    And the "linking/static" package contains:
      """
      linking: ["static", "dynamic"]
      """
    When I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Mismatch between linking type:
        - linking/any (dynamic)
        - linking/linkToStatic -> linking/any (static)
      """
    And command should exit with status code 1

  Scenario: Install locked version of the dependencies
    Given there is package which requires:
      | package   | version |
      | basic/log | *       |
    And local repository contains packages:
      | package   | version |
      | basic/log | 1.0.0   |
    When I run "deplink install --no-progress"
    And remove "deplinks" folder
    And upgrade packages:
      | package   | version |
      | basic/log | 1.1.0   |
    And I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Dependencies: 1 install, 0 updates, 0 removals
        - Installing basic/log (v1.0.0)
      """

  Scenario: Update packages which changed version constraint is inconsistent with locked version
    Given there is package which requires:
      | package   | version |
      | basic/log | 1.*     |
    And local repository contains packages:
      | package   | version |
      | basic/log | 1.2.0   |
    When I run "deplink install --no-progress"
    And change global package requirements:
      | package   | version |
      | basic/log | 2.*     |
    And upgrade packages:
      | package   | version |
      | basic/log | 2.0.0   |
    And I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Dependencies: 0 install, 1 updates, 0 removals
        - Updating basic/log (v1.2.0 -> v2.0.0)
      """

  Scenario: Install package not listed in deplink.lock file
    Given there is package which requires:
      | package   | version |
      | basic/log | *       |
    And local repository contains packages:
      | package    | version |
      | basic/log  | 1.0.0   |
      | basic/unit | 1.0.0   |
    When I run "deplink install --no-progress"
    And change global package requirements:
      | package    | version |
      | basic/log  | *       |
      | basic/unit | *       |
    And I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Dependencies: 1 install, 0 updates, 0 removals
        - Installing basic/unit (v1.0.0)
      """
    And command should exit with status code 0

  # TODO: cleanup deplinks directory if installed.lock file is missing
  # TODO: delete mismatches between installed.lock and directory structure

  # TODO: specify new package to install
  # TODO: install multiple libs at once
  # TODO: check packages compiler compatibility
  # TODO: check packages platform compatibility
  # TODO: check packages architecture compatibility
  # TODO: dependency defined in both dependencies and dev-dependencies section

  # TODO: Remove packages
  # TODO: Install local package without specified version (default 0.1.0), each installation should reinstall package
  # TODO: Test script callbacks
  # TODO: --no-dev option

  # TODO: install newest available and compatible version (how to test? local repository allows hosting only one version)
