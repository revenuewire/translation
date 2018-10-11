<?php
/**
 * Created by IntelliJ IDEA.
 * User: swang
 * Date: 2017-09-20
 * Time: 9:44 AM
 */

class IDTest extends \PHPUnit\Framework\TestCase
{
    function testId()
    {
        $this->assertSame("0068a3e279a2b201279bed816392278139aa0324", \RW\Translation::idFactory("en", "Product", ""));
        $this->assertSame("8a62bdfc5ffce9c4721ab6666af887a292e39a80", \RW\Translation::idFactory("en", "product", ""));
        $this->assertSame( "0d182936345cdacf8844d4d3ea4c499ae2854912", \RW\Translation::idFactory("fr", "Product", ""));
        $this->assertSame( "3597829b7d7bd8f5aabbafb862044ec41c0c6382", \RW\Translation::idFactory("fr", "product", ""));
    }
}