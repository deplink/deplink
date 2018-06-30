Feature: List command
  Get list of installed dependencies

  Scenario: List dependencies with --no-dev option
    Given there is package which requires:
      | package    | version | isDev |
      | basic/log  | *       | true  |
      | basic/unit | *       | false |
    And local repository contains packages:
      | package    | version |
      | basic/log  | v1.0.3  |
      | basic/unit | v1.0.5  |
    When I run "deplink list --no-dev"
    Then command should exit with status code 0
    And the console output should contains:
      """
      basic/unit (v1.0.5) - out-of-date
      """

  Scenario: Project without dependencies
    Given there is empty package
    When I run "deplink list"
    Then the console output should be empty

  Scenario: Detect dependencies out-of-date
    Given there is package which requires:
      | package    | version |
      | basic/unit | *       |
    And local repository contains packages:
      | package    | version |
      | basic/unit | v1.0.0  |
    When I run "deplink list"
    Then the console output should contains:
      """
      basic/unit (v1.0.0) - out-of-date
      """
    When I run "deplink install"
    And I run "deplink list"
    Then the console output should contains:
      """
      basic/unit (v1.0.0)
      """

  Scenario: Dependencies not listed in deplink.json
    Given there is package which requires:
      | package      | version |
      | hello/lipsum | *       |
    And local repository contains packages:
      | package      | version |
      | hello/lipsum | v1.1.8  |
      | hello/world  | v2.0.3  |
    When I run "deplink list"
    Then the console output should contains:
      """
      hello/lipsum (v1.1.8) - out-of-date
      hello/world (v2.0.3) - out-of-date
      """

  Scenario: Sort results ascending by name
    Given there is package which requires:
      | package      | version |
      | chain/link-b | *       |
      | hello/lipsum | *       |
      | basic/math   | *       |
      | basic/log    | *       |
    And local repository contains packages:
      | package      | version |
      | basic/math   | v1.0.0  |
      | basic/log    | v1.0.0  |
      | chain/link-b | v1.0.0  |
      | chain/link-c | v1.0.0  |
      | hello/lipsum | v1.0.0  |
      | hello/world  | v1.0.0  |
    When I run "deplink install"
    When I run "deplink list"
    Then the console output should contains:
      """
      basic/log (v1.0.0)
      basic/math (v1.0.0)
      chain/link-b (v1.0.0)
      chain/link-c (v1.0.0)
      hello/lipsum (v1.0.0)
      hello/world (v1.0.0)
      """
