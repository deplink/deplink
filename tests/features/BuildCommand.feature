Feature: Build command
  Build downloaded libraries and project (executable/library).

  @unimplemented
  Scenario: Empty project
    Given there is empty package
    When I run "deplink build --no-progress"
    Then command should exit with status code 1
    And the console output should contains:
      """
      Source files not found. By default source files must be placed in the 'src' dir (you can configure it by changing 'source' in deplink.json file).
      """

  Scenario: Project without dependencies
    Given there is empty package
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
      Dependencies: 0 builds, 0 up-to-date
      Building project... OK
      """
    And command should exit with status code 0
    And I should have 1 of files:
      | path                      |
      | build/x86/org-package.exe |
      | build/x86/org-package     |

  Scenario: One dependency not previously installed
    Given I am in "org/package" directory
    And there is package which requires:
      | package     | version |
      | hello/world | *       |
    And local repository contains packages:
      | package     | version |
      | hello/world | 1.0.0   |
    And there is "src/main.cpp" file with contents:
      """
      #include "autoload.h"

      int main() {
        Hello::World::sayHello("John");
        return 0;
      }
      """
    When I run "deplink build --no-progress"
    Then the console output should contains:
      """
      Retrieving installed dependencies... Skipped
      Resolving dependencies tree... OK
      Dependencies: 1 installs, 0 updates, 0 removals
        - Installing hello/world (v1.0.0)
      Writing lock file... OK
      Generating autoload header... OK
      Dependencies: 1 builds, 0 up-to-date
        - Building hello/world
      Building project... OK
      """
    And command should exit with status code 0
    And I should have 1 of files:
      | path                      |
      | build/x86/org-package.exe |
      | build/x86/org-package     |

  Scenario: Custom compiler options for x64 build only
    Given there is empty package
    And there is "deplink.json" file with contents:
      """
      {
        "name": "test/package",
        "type": "project",
        "compilers": {
          "g++": "*"
        },
        "config": {
          "compilers": {
            "g++:x64": "-save-temps=obj"
          }
        }
      }
      """
    And there is "src/main.cpp" file with contents:
      """
      #include <cstdio>

      int main() {
        return 0;
      }
      """
    # Now we build with disabled intermediate files, but
    # for x64 build these files should still be available
    # because -save-temps option was added manually.
    When I run "deplink build --no-dev --no-progress"
    Then command should exit with status code 0
    And I should have file "build/x64/test-package.o"
    And I shouldn't have file "build/x86/test-package.o"

  Scenario: Copy libraries to build directory
    Given I am in "org/package" directory
    And there is package which requires:
      | package     | version   |
      | hello/world | *:dynamic |
    And there is "src/main.cpp" file with contents:
      """
      #include "autoload.h"

      int main() {
        Hello::World::sayHello("John");
        return 0;
      }
      """
    When I run "deplink build --compiler=g++ --no-progress"
    Then command should exit with status code 0
    And I should have 1 of files:
      | path                        |
      | build/x86/hello-world.dll   |
      | build/x86/libhello-world.so |

  Scenario: Rebuild only root project on second call
    Given I am in "org/package" directory
    And there is package which requires:
      | package     | version |
      | hello/world | *       |
    And there is "src/main.cpp" file with contents:
      """
      #include "autoload.h"

      int main() {
        Hello::World::sayHello("John");
        return 0;
      }
      """
    When I run "deplink build --no-progress"
    Then the console output should contains:
      """
      Dependencies: 1 builds, 0 up-to-date
        - Building hello/world
      Building project... OK
      """
    When I run "deplink build --no-progress"
    Then the console output should contains:
      """
      Dependencies: 0 builds, 1 up-to-date
      Building project... OK
      """

  Scenario: Treat .cpp files as .c with gcc
    # Following program compiles & runs fine in C, but fails in compilation in C++.
    # Const variable in C++ must be initialized but in c it isn’t necessary.
    # Source: https://www.geeksforgeeks.org/write-c-program-wont-compiler-c
    Given there is empty package
    And there is "src/main.cpp" file with contents:
      """
      #include <stdio.h>

      int main()
      {
          const int a;
          return 0;
      }
      """
    When I run "deplink build --compiler=gcc --no-progress"
    Then command should exit with status code 0
    When I run "deplink build --compiler=g++ --no-progress"
    Then command should not exit with status code 0

  Scenario: Build project with chained dependencies
    Given there is package which requires:
      | package      | version |
      | chain/link-a | *       |
    And the "chain/link-a" package requires:
      | package      | version |
      | chain/link-b | *       |
    And the "chain/link-b" package requires:
      | package      | version |
      | chain/link-c | *       |
    And there is "src/main.cpp" file with contents:
      """
      #include "autoload.h"

      int main() {
        printABC();
        return 0;
      }
      """
    When I run "deplink build --no-progress"
    Then the console output should contains:
      """
      Dependencies: 3 builds, 0 up-to-date
        - Building chain/link-c
        - Building chain/link-b
        - Building chain/link-a
      Building project... OK
      """
    When I run "deplink run"
    Then the console output should contains "ABC"

  Scenario: Print commands in verbose mode
    # Example of the full g++ output for Windows and Linux
    # (include absolute paths which was truncated in test):
    #
    # Windows:
    # > Building project...
    # > g++ -x c++ src/main.cpp -m32 -o build/x86/org-package.exe -g -save-temps=obj -L /path/to/project/build/x86 -I "include" -I "deplinks" -Wall -O3
    # > g++ -x c++ src/main.cpp -m64 -o build/x64/org-package.exe -g -save-temps=obj -L /path/to/project/build/x64 -I "include" -I "deplinks" -Wall -O3
    # > OK
    #
    # Linux:
    # > Building project...
    # > g++ -x c++ src/main.cpp -m32 -o build/x86/org-package -g -save-temps=obj -L /path/to/project/build/x86 -I 'include' -I 'deplinks' -Wall -O3 -fPIC
    # > g++ -x c++ src/main.cpp -m64 -o build/x64/org-package -g -save-temps=obj -L /path/to/project/build/x64 -I 'include' -I 'deplinks' -Wall -O3 -fPIC
    # > OK
    Given there is empty package
    And there is "src/main.cpp" file with contents:
    """
    #include <cstdio>

    int main() {
      printf("Hello, World!");
      return 0;
    }
    """
    When I run "deplink build --verbose --no-progress"
    Then the console output should contains:
      """
      Building project...
      g++ -x c++ src/main.cpp -m32
      """

  # TODO: If arch is set to x86 then dependencies should be build only using the x86 arch (only build/x86 dir should exists)
  # TODO: User friendly message when I select invalid compiler via --compiler option
