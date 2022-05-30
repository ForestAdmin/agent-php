<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

class ForestServer
{
    //public const ColumnType;

    /*public const collection = [
            'name' => string,
            'icon' => null,
            'integration' => null,
            'isReadOnly' => boolean,
            'isSearchable' => boolean,
            'isVirtual' => false,
            'onlyForRelationships' => boolean,
            'paginationType' => 'page',
            'actions' => Array<ForestServerAction>,
          'fields' => Array<ForestServerField>,
          'segments' => Array<ForestServerSegment>,
    ];
    */
    public const action =  [
        //    id => string;
        //    name => string;
        //    type => 'single' | 'bulk' | 'global';
        //    baseUrl => string;
        //    endpoint => string;
        //    httpMethod => 'POST';
        //    redirect => unknown;
        //    download => boolean;
        //    fields => ForestServerActionField[];
        //    hooks => {
        //        load => boolean;
        //        change => Array<unknown>;
        //  };
    ];

    public const actionField = [
        //    value => unknown;
        //    defaultValue => unknown;
        //    description => string | null;
        //    enums => string[];
        //    field => string;
        //    hook => string;
        //    isReadOnly => boolean;
        //    isRequired => boolean;
        //    reference => string | null;
        //    type => ForestServerColumnType;
        //    widget => null | 'belongsto select' | 'file picker';
    ];

    public const field = [
        //    field => string;
        //    type => ForestServerColumnType;
        //    defaultValue => unknown;
        //    enums => null | string[];
        //    integration => null; // Always null on forest-express
        //    isFilterable => boolean;
        //    isPrimaryKey => boolean;
        //    isReadOnly => boolean;
        //    isRequired => boolean;
        //    isSortable => boolean;
        //    isVirtual => boolean; // Computed. Not sure what is done with that knowledge on the frontend.
        //    reference => null | string;
        //    inverseOf => null | string;
        //    relationship => 'BelongsTo' | 'BelongsToMany' | 'HasMany' | 'HasOne';
        //    validations => Array<{ message => null; type => ValidationType; value => unknown }>;
    ];

    public const segment = [
        //    id => string;
        //    name => string;
        //};
        //
        //export enum ValidationType {
        //Present = 'is present',
        //GreaterThan = 'is greater than',
        //LessThan = 'is less than',
        //Before = 'is before',
        //After = 'is after',
        //LongerThan = 'is longer than',
        //ShorterThan = 'is shorter than',
        //Contains = 'contains',
        //Like = 'is like',
    ];
}
//
//
//import { PrimitiveTypes } from '@forestadmin/datasource-toolkit';
//
//export type ForestServerColumnType =
//  | PrimitiveTypes
//| [ForestServerColumnType]
//| { fields: Array<{ field: string; type: ForestServerColumnType }> };
//
//export type ForestServerCollection = {
//    name: string;
//    icon: null;
//    integration: null;
//    isReadOnly: boolean;
//    isSearchable: boolean;
//    isVirtual: false;
//    onlyForRelationships: boolean;
//    paginationType: 'page';
//    actions: Array<ForestServerAction>;
//  fields: Array<ForestServerField>;
//  segments: Array<ForestServerSegment>;
//};
//
//export type ForestServerAction = {
//    id: string;
//    name: string;
//    type: 'single' | 'bulk' | 'global';
//    baseUrl: string;
//    endpoint: string;
//    httpMethod: 'POST';
//    redirect: unknown;
//    download: boolean;
//    fields: ForestServerActionField[];
//    hooks: {
//        load: boolean;
//        change: Array<unknown>;
//  };
//};
//
//export type ForestServerActionField = {
//    value: unknown;
//    defaultValue: unknown;
//    description: string | null;
//    enums: string[];
//    field: string;
//    hook: string;
//    isReadOnly: boolean;
//    isRequired: boolean;
//    reference: string | null;
//    type: ForestServerColumnType;
//    widget: null | 'belongsto select' | 'file picker';
//};
//
//export type ForestServerField = Partial<{
//    field: string;
//    type: ForestServerColumnType;
//    defaultValue: unknown;
//    enums: null | string[];
//    integration: null; // Always null on forest-express
//    isFilterable: boolean;
//    isPrimaryKey: boolean;
//    isReadOnly: boolean;
//    isRequired: boolean;
//    isSortable: boolean;
//    isVirtual: boolean; // Computed. Not sure what is done with that knowledge on the frontend.
//    reference: null | string;
//    inverseOf: null | string;
//    relationship: 'BelongsTo' | 'BelongsToMany' | 'HasMany' | 'HasOne';
//    validations: Array<{ message: null; type: ValidationType; value: unknown }>;
//}>;
//
//export type ForestServerSegment = {
//    id: string;
//    name: string;
//};
//
//export enum ValidationType {
//Present = 'is present',
//GreaterThan = 'is greater than',
//LessThan = 'is less than',
//Before = 'is before',
//After = 'is after',
//LongerThan = 'is longer than',
//ShorterThan = 'is shorter than',
//Contains = 'contains',
//Like = 'is like',
//}
