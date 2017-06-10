Feature: Init command
  Create empty deplink.json file.

  Scenario: Initializing package in empty directory
    Given I am in "org/package" directory
    When I run "deplink init"
    Then the console output should contains "Creating deplink.json file... OK"
    And I should have file "deplink.json" with contents:
      """
      {
        "name": "org/package",
        "type": "project"
      }
      """

  Scenario: Specifying package name
    When I run "deplink init hello/world"
    Then I should have file "deplink.json" which contains "\"name\": \"hello/world\""

  Scenario: Changing working directory (--working-dir option)
    Given I am in "org/package" directory
    When I run "deplink init --working-dir=../"
    Then I should have file "../deplink.json"
    But I shouldn't have file "deplink.json"

  Scenario: Changing working directory (-d option)
    Given I am in "org/package" directory
    When I run "deplink init -d=../"
    Then I should have file "../deplink.json"
    But I shouldn't have file "deplink.json"

  Scenario: Working in previously initialized directory
    Given there is "deplink.json" file
    When I run "deplink init"
    Then the console output should contains "Creating deplink.json file... FAIL"
    And the console output should contains "Package already exists in given directory"

  Scenario: Working in non-empty directory
    Given I am in "org/package" directory
    And there is "example.txt" file
    When I run "deplink init"
    Then the console output should contains "Creating deplink.json file... FAIL"
    And the console output should contains "Cannot initialize package in non-empty directory"
