Feature: Run command
  Execute compiled project

  Scenario: Simple program
    Given there is empty package
    And there is "src/main.cpp" file with contents:
      """
      #include <cstdio>

      int main() {
        printf("Hello, World!");
        return 230;
      }
      """
    When I run "deplink build --no-progress"
    And I run "deplink run"
    Then the console output should contains:
      """
      Hello, World!
      """
    And command should exit with status code 230
