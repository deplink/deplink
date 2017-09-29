Feature: Build command
  Build downloaded libraries and project (executable/library).

  Scenario: Empty project
    Given there is empty package
    When I run "deplink build --no-progress"
    Then command should exit with status code 1
    And the console output should contains:
      """
      Neither source nor header files found. By default headers are placed in 'include' dir and source files in 'src' dir (you can configure it by changing 'include' and 'source' keys in deplink.json file).
      """

  Scenario: Project without dependencies
    Given I am in "custom/name" directory
    And there is empty package
    And there is "src/main.cpp" file with contents:
    """
    #include <cstdio>

    int main() {
      printf("Hello, World!");
      return 0;
    }
    """
    When I run "deplink build --no-progress"
    Then the console output should contains:
      """
      Retrieving installed dependencies... Skipped
      Resolving dependencies tree... OK
      Dependencies: 0 installs, 0 updates, 0 removals
      Writing lock file... OK
      Generating autoload header... OK
      Building project... OK
      """
    And command should exit with status code 0
    And I should have 1 of files:
      | path                  |
      | build/x86/package.exe |
      | build/x86/package     |

  Scenario: One dependency not previously installed
    Given I am in "org/package" directory
    And there is package which requires:
      | package     | version |
      | hello/world | *       |
    When I run "deplink build --no-progress"
    Then the console output should contains:
      """
      Retrieving installed dependencies... Skipped
      Resolving dependencies tree... OK
      Dependencies: 1 installs, 0 updates, 0 removals
        - Installing hello/world (v1.0.0)
      Writing lock file... OK
      Generating autoload header... OK
      Dependencies: 2 builds, 0 up-to-date
        - Building hello/world (x86)
        - Building hello/world (x64)
      Building project... OK
      """
    And command should exit with status code 0
    And I should have 1 of files:
      | path                  |
      | build/x86/package.exe |
      | build/x86/package     |
    And I should have 1 of files:
      | path                  |
      | build/x64/package.exe |
      | build/x64/package     |