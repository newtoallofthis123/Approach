<?php

namespace Tests\Unit;

use Approach\Resource\FilterParser;
use Approach\Resource\Resource;
use Tests\Support\UnitTester;

class ParserCest
{
    public function checkResourceParse(UnitTester $I)
    {
        $url = "MariaDB://db.host/instances[rate gt 1000]/myDatabase/myTable[price le 250 AND id eq 1, status: active, updated: 12-31-2022, cool: [id le 12]][id, name].getFile()?hello=world";
        $resource = new Resource();
        $r = $resource->parseUri($url);
        $r['properties'] = $resource->properties;
        $r['comparisons'] = $resource->comparisons;
        var_export($r);
        $I->assertEquals($r['scheme'], "MariaDB");
    }
}
