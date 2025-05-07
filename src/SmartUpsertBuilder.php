<?php

namespace Sakusi4\LaravelSmartUpsert;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SmartUpsertBuilder
{
    /**
     *
     * @param string $modelClass
     * @param array $rows [ ['col'=>'val', …], … ]
     * @param string $uniqueBy 중복 판별용 키 컬럼 목록
     * @param array $compareFields 변경 감지용 필드 목록
     */
    public static function upsert(string $modelClass, array $rows, string $uniqueBy, array $compareFields): void
    {
        if (empty($rows)) {
            return;
        }

        $model = new $modelClass;
        $table = $model->getTable();
        $fillable = $model->getFillable();

        try {
            $key = $uniqueBy;
            $ids = array_column($rows, $key);
            $existing = DB::table($table)
                ->whereIn($key, $ids)
                ->get(array_merge([$key], $compareFields))
                ->keyBy($key)
                ->toArray();

            $toInsert = [];
            $toUpdate = [];

            foreach ($rows as $row) {
                $id = $row[$key];
                if (!isset($existing[$id])) {
                    $toInsert[] = array_intersect_key($row, array_flip(
                        array_merge([$key], $fillable)
                    ));
                } else {
                    // 변경 감지
                    $dirty = false;
                    foreach ($compareFields as $f) {
                        if (($existing[$id]->{$f} ?? null) != ($row[$f] ?? null)) {
                            $dirty = true;
                            break;
                        }
                    }
                    if ($dirty) {
                        $toUpdate[] = array_intersect_key($row, array_flip(
                            array_merge([$key], $fillable)
                        ));
                    }
                }
            }

            if (!empty($toInsert)) {
                DB::table($table)->insert($toInsert);
            }

            if (!empty($toUpdate)) {
                $cases = [];
                $bindings = [];
                foreach ($fillable as $field) {
                    if ($field === $key) {
                        continue;
                    }
                    $case = "CASE `$key` ";
                    foreach ($toUpdate as $row) {
                        $case .= "WHEN ? THEN ? ";
                        $bindings[] = $row[$key];
                        $bindings[] = $row[$field];
                    }
                    $case .= "ELSE `$field` END";
                    $cases[] = "`$field` = $case";
                }

                $idsToUpdate = array_column($toUpdate, $key);
                $inClause = implode(',', array_fill(0, count($idsToUpdate), '?'));
                $bindings = array_merge($bindings, $idsToUpdate);

                $sql = sprintf(
                    "UPDATE `%s` SET %s WHERE `%s` IN (%s)",
                    $table,
                    implode(', ', $cases),
                    $key,
                    $inClause
                );

                DB::update($sql, $bindings);
            }

        } catch (QueryException $e) {
            throw $e;
        }
    }
}
