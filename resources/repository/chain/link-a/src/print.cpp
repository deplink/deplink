#include <stdio.h>
#include "autoload.h"

void printA()
{
    printf("A");
}

void printAB()
{
    printf("A");
    printB();
}

void printAC()
{
    printf("A");
    printC();
}

void printABC()
{
    printf("A");;
    printB();
    printC();
}

