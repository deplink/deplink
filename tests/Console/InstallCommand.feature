Feature: Install command
  Download libraries and prepare autoload header

  Scenario: First installation
    Given there is package which requires:
      | package     | version |
      | hello/world | *       |
    And local repository contains packages:
      | package     | version |
      | hello/world | 1.0.0   |
    When I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Retrieving installed dependencies... OK
      Retrieving available dependencies... OK
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
      Retrieving available dependencies... OK
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
      Retrieving available dependencies... OK
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

  # TODO: install locked version of the dependencies
  # TODO: install packages not listed in deplink.lock file
  # TODO: update packages which changed version constraint is inconsistent with locked version

  # TODO: install only new dependencies
  # TODO: install only updated dependencies
  # TODO: cleanup deplinks directory if installed.lock file is missing
  # TODO: delete mismatches between installed.lock and directory structure

  # TODO: specify new package to install
  # TODO: check packages compiler compatibility
  # TODO: check packages platform compatibility
  # TODO: check packages architecture compatibility

  # TODO: install multiple libs at once
