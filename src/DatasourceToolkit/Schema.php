<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

class Schema
{
    //public function

    /*
     *
     *
        // PUBLIC FUNCTION
        export type ActionSchema = {
          scope: ActionScope;
          generateFile?: boolean;
          staticForm?: boolean;
        };

        // PUBLIC FUNCTION
        export type DataSourceSchema = {
          charts: string[];
        };

        // PUBLIC FUNCTION
        export type CollectionSchema = {
          actions: { [actionName: string]: ActionSchema };
          fields: { [fieldName: string]: FieldSchema };
          searchable: boolean;
          segments: string[];
        };

        // PUBLIC FUNCTION RELATION
        export type RelationSchema = ManyToOneSchema | OneToManySchema | OneToOneSchema | ManyToManySchema;
        export type FieldSchema = ColumnSchema | RelationSchema;

        // PUBLIC FUNCTION
        export type ColumnSchema = {
          columnType: ColumnType;
          filterOperators?: Set<Operator>;
          defaultValue?: unknown;
          enumValues?: string[];
          isPrimaryKey?: boolean;
          isReadOnly?: boolean;
          isSortable?: boolean;
          type: 'Column';
          validation?: Array<{ operator: Operator; value?: unknown }>;
        };

     *
     *
     */
}
