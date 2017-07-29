#include "code.hpp"
#include "autoload.h"

void Hello::Lipsum::sayHello(const char* name)
{
    Hello::World::sayHello(name);
}
