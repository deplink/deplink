Feature: Install command
  Download libraries and prepare autoload header.

  Scenario: First installation
    Given there is package which requires:
      | package     | version |
      | hello/world | *       |
    And local repository contains packages:
      | package     | version |
      | hello/world | v1.0.0  |
    When I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Retrieving installed dependencies... Skipped
      Resolving dependencies tree... OK
      Dependencies: 1 installs, 0 updates, 0 removals
        - Installing hello/world (v1.0.0)
      Writing lock file... OK
      Generating autoload header... OK
      """
    And command should exit with status code 0
    And the "deplinks/autoload.h" file should contains:
      """
      #pragma once

      #include "hello/world/include/main.hpp"
      """

  Scenario: The deplink.json file not exists
    Given file "deplink.json" not exists
    When I run "deplink install"
    And the console output should contains "Working directory is not the deplink project (check path or initialize project using `deplink init` command)"
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
      | hello/world  | v1.0.0  |
      | hello/lipsum | v1.0.0  |
    When I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Retrieving installed dependencies... Skipped
      Resolving dependencies tree... OK
      Dependencies: 2 installs, 0 updates, 0 removals
        - Installing hello/lipsum (v1.0.0)
        - Installing hello/world (v1.0.0)
      Writing lock file... OK
      Generating autoload header... OK
      """
    And command should exit with status code 0
    And the "deplinks/autoload.h" file should contains:
      """
      #pragma once

      #include "hello/lipsum/include/code.hpp"
      #include "hello/world/include/main.hpp"
      """

  Scenario: Install without nested dev dependencies
    Given there is package which requires:
      | package    | version | isDev |
      | basic/log  | *       | true  |
      | basic/math | *       | false |
    And the "basic/math" package requires:
      | package    | version | isDev |
      | basic/unit | *       | true  |
    And local repository contains packages:
      | package    | version |
      | basic/log  | v1.0.0  |
      | basic/unit | v1.0.0  |
      | basic/math | v1.0.0  |
    When I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Retrieving installed dependencies... Skipped
      Resolving dependencies tree... OK
      Dependencies: 2 installs, 0 updates, 0 removals
        - Installing basic/log (v1.0.0)
        - Installing basic/math (v1.0.0)
      Writing lock file... OK
      Generating autoload header... OK
      """
    And command should exit with status code 0

  Scenario: Install without root dev dependencies
    Given there is package which requires:
      | package    | version | isDev |
      | basic/log  | *       | true  |
      | basic/math | *       | false |
    And the "basic/math" package requires:
      | package    | version | isDev |
      | basic/unit | *       | true  |
    And local repository contains packages:
      | package    | version |
      | basic/math | v1.0.0  |
    When I run "deplink install --no-progress --no-dev"
    Then the console output should contains:
      """
      Retrieving installed dependencies... Skipped
      Resolving dependencies tree... OK
      Dependencies: 1 installs, 0 updates, 0 removals
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
      Dependencies: 0 installs, 0 updates, 0 removals
      """
    And command should exit with status code 0

  Scenario: Detect dependencies loop
    Given there is package which requires:
      | package     | version |
      | loops/lib-a | *       |
    And the "loops/lib-a" package requires:
      | package     | version |
      | loops/lib-b | *       |
    And the "loops/lib-b" package requires:
      | package     | version |
      | loops/lib-a | *       |
    When I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Dependencies loop detected: loops/lib-a -> loops/lib-b -> loops/lib-a
      """
    And command should exit with status code 1

  @unimplemented
  Scenario: Detect mismatch between linking type
    Given there is package which requires:
      | package        | version   |
      | linking/any    | *:dynamic |
      | linking/static | *         |
    And the "linking/static" package requires:
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
        - linking/static -> linking/any (static)
      """
    And command should exit with status code 1

  Scenario: Update package after requirements change
    Given there is package which requires:
      | package   | version |
      | basic/log | *       |
    And local repository contains packages:
      | package   | version |
      | basic/log | v1.2.0  |
    When I run "deplink install --no-progress"
    And upgrade packages:
      | package   | version |
      | basic/log | v2.0.0  |
    And change global package requirements:
      | package   | version |
      | basic/log | 2.*     |
    And I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Dependencies: 0 installs, 1 updates, 0 removals
        - Updating basic/log (v1.2.0 -> v2.0.0)
      """

  Scenario: Dependencies tree conflict (versions)
    Given there is package which requires:
      | package   | version |
      | basic/log | 0.1.0   |
    And local repository contains packages:
      | package   | version |
      | basic/log | v1.2.0  |
    When I run "deplink install --no-progress"
    Then the console output should contains:
      """
      None of the available versions for 'basic/log' dependency match requested constraints:
       - available versions: v1.2.0.
       - requested constraints: 0.1.0.
      """

  # TODO: Upgrade locked dependency along with deplink.json (requires remote repository)

  Scenario: Install package not listed in deplink.lock file
    Given there is package which requires:
      | package   | version |
      | basic/log | *       |
    And local repository contains packages:
      | package    | version |
      | basic/log  | v1.0.0  |
      | basic/unit | v1.0.0  |
    When I run "deplink install --no-progress"
    And change global package requirements:
      | package    | version |
      | basic/log  | *       |
      | basic/unit | *       |
    And I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Dependencies: 1 installs, 0 updates, 0 removals
        - Installing basic/unit (v1.0.0)
      """
    And command should exit with status code 0

  Scenario: Install with --dev and --no-dev option
    Given there is empty package
    When I run "deplink install --dev --no-dev"
    Then the console output should contains:
      """
      Cannot use --dev option along with --no-dev option.
      """

  @remote
  Scenario: Install dependency from official remote repository
    Given there is empty package
    When I run "deplink install deplink/sample --no-progress"
    Then the console output should contains:
      """
      Dependencies: 1 installs, 0 updates, 0 removals
        - Installing deplink/sample (v1.0.0)
      """

  @linux
  Scenario: Prevents creating cache in project directory
    # There was an error on Linux which causes to store cached packages
    # in the project directory in "~" folder (it should point to home dir).
    Given there is empty package
    When I run "deplink install deplink/sample --no-progress"
    Then I shouldn't have directory "~"

  Scenario: Revert changes in deplink.json after exception
    Given there is empty package
    When I run "deplink install package/not-exists --no-progress"
    Then the console output should contains "The 'package/not-exists' package was not found"
    And I shouldn't have file "deplink.lock"
    And the "deplink.json" file shouldn't contains:
    """
    "package/not-exists"
    """

  Scenario: Fix empty deplink.json file after installation
    Given there is "deplink.json" file with contents:
      """
      {
          "name": "org/package",
          "type": "project",
          "dependencies": {
              "_/this-package-should-not-exists": "*"
          }
      }
      """
    When I run "deplink install"
    Then command should not exit with status code 0
    And I should have file "deplink.json" with contents:
      """
      {
          "name": "org/package",
          "type": "project",
          "dependencies": {
              "_/this-package-should-not-exists": "*"
          }
      }
      """

  Scenario: Do not remove packages which are not installed
    # Issue #15: https://github.com/deplink/deplink/issues/15
    Given there is package which requires:
      | package   | version |
      | basic/log | *       |
    And local repository contains packages:
      | package   | version |
      | basic/log | v1.0.0  |
    When I run "deplink install --no-progress"
    And I remove "deplinks" folder
    And I run "deplink install --no-progress"
    Then the console output should contains:
      """
      Dependencies: 1 installs, 0 updates, 0 removals
        - Installing basic/log (v1.0.0)
      """

  # TODO: check if cache directory is created in home directory (no in poject dir "~" - issue with tilde symbol)

  # TODO: Install locked version of the dependencies (require remote repository)
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
  # TODO: Different package name than local repository directory

  # TODO: install newest available and compatible version (how to test? local repository allows hosting only one version)
  # TODO: Online repository tests

  # TODO: install version A (A < B), remove deplinks dir and repeat install (should install A)
  # TODO: install version A (A < B), update to B, repeat install (should update to B, check lock file)

  # TODO: add test for script at homepage (deplink.org)
  # TODO: add test for script from getting started page (first project)

  # TODO: package exists in 2 repositories, which one was previously installed and locked in deplink.lock? First repository could add package later
