Feature: Init command
  Create basic deplink.json file

  Scenario: Initializing package in empty directory
    Given I am in "org/package" directory
    When I run "deplink init"
    Then the console output should contains "Creating deplink.json file... OK"
    And command should exit with status code 0
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
    And directory "../package2" exists
    When I run "deplink init --working-dir ../package2"
    Then I should have file "../package2/deplink.json"
    But I shouldn't have file "deplink.json"

  Scenario: Changing working directory (-d option)
    Given I am in "org/package" directory
    And directory "../package2" exists
    When I run "deplink init -d ../package2"
    Then the console output should contains "Creating deplink.json file... OK"
    Then I should have file "../package2/deplink.json"
    But I shouldn't have file "deplink.json"

  Scenario: Working in previously initialized directory
    Given there is "deplink.json" file
    When I run "deplink init"
    And the console output should contains "Package already exists in given directory"
    And command should exit with status code 1

  Scenario: Working in non-empty directory
    Given I am in "org/package" directory
    And there is "example.txt" file
    When I run "deplink init"
    And the console output should contains "Cannot initialize package in non-empty directory"
    And command should exit with status code 1

  Scenario Outline: Invalid package name information
    When I run "deplink init <packageName>"
    And the console output should contains "Invalid '<packageName>' package name, use org/package format (use only letters, digits and dashes)"
    And command should exit with status code 1

    Examples:
      | packageName  |
      | /            |
      | org          |
      | org/         |
      | package      |
      | /package     |
      | org//package |
      | __/package   |
      | org/___      |

  Scenario: Working directory not found
    When I run "deplink init -d ./not_exists"
    And the console output should contains "The specified './not_exists' working directory is not accessible"
    And command should exit with status code 1
