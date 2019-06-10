<?php

namespace hiqdev\composer\config\tests\unit;

if (version_compare(PHP_VERSION, '5.6', '>=')) {
    trait Polyfill
    {
    }
} else {
    trait Polyfill
    {
        protected function createMock($originalClassName)
        {
            return $this->getMockBuilder($originalClassName)
                ->disableOriginalConstructor()
                ->disableOriginalClone()
                ->disableArgumentCloning()
                ->getMock();
        }
    }
}
