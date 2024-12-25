#!/bin/bash
find ./cache -type f -name '*.json' -size -1500c -delete
