#include <stdio.h>
#include "autoload.h"

void printB()
{
    printf("B");
}

void printBC()
{
    printf("B");
    printC();
}
