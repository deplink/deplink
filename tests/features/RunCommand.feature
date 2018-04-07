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

  Scenario: One dependency not previously installed
    Given I am in "org/package" directory
    And there is package which requires:
      | package     | version |
      | hello/world | *       |
    And there is "src/main.cpp" file with contents:
      """
      #include "autoload.h"

      int main(int argc, const char** argv) {
        Hello::World::sayHello(argv[1]);
        Hello::World::sayHello(argv[2]);
        return 0;
      }
      """
    When I run "deplink build --no-progress"
    And I run "deplink run -- Wojtek World"
    Then the console output should contains:
      """
      Hello, Wojtek!
      Hello, World!
      """

  @remote
  Scenario: Dependency from official remote repository
    Given there is empty package
    And there is "src/main.cpp" file with contents:
      """
      #include "autoload.h"

      int main(int argc, const char** argv) {
        sayHello(argv[1]);
        return 0;
      }
      """
    When I run "deplink install deplink/sample --no-progress"
    And I run "deplink build"
    And I run "deplink run -- Wojtek"
    Then the console output should contains:
      """
      "Hello, Wojtek" said Deplink, beaming at him.
      """

    # TODO: Setting the LD_LIBRARY_PATH on Linux (http://tldp.org/HOWTO/Program-Library-HOWTO/shared-libraries.html)
    # TODO: Default working directory in the running application
