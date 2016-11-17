package main

// export_flats: выгрузка данных в файлы типа Шаблон импорт сведений о МКД-УО-x.x.x.x

import (
	xlsx2 "github.com/Luxurioust/excelize"
	"github.com/tealeg/xlsx"
)

func init() {
	mapFnc["export_flats"] = export_flats
}

func init_export_flats() {
	mapFnc2["Характеристики МКД"] = export_flats_fill_house1
}

func export_flats(opt map[string]interface{}) error {
	init_export_flats()
	//excelFileName := opt["from"].(string)
	//xlFileR, err := xlsx.OpenFile(excelFileName)
	//LogPrintErrAndExit("ошибка открытия1 ексель файла "+excelFileName, err)

	excelFileName := opt["to"].(string)
	xlFile, err := xlsx.OpenFile(excelFileName)
	LogPrintErrAndExit("ошибка открытия2 ексель файла "+excelFileName, err)

	/****
		for i, sheet := range xlFile.Sheets {
			name := sheet.Name
			fnc, ok := mapFnc2[name]
			if !ok {
				//fmt.Printf("skip sheet %s\n", name)
				continue
			}
			//xlFile.SetActiveSheet(i)
			LogPrint(Fmts("Обработка листа%d \""+name+"\"", i))
			opt["cur_i_sheet_name"] = name
			//err := fnc(xlFile, opt)
			err := fnc(sheet, opt)
			LogPrintErrAndExit("ошибка при заполнении документа: \n\""+excelFileName+"\"\nлист:\""+name+"\"\n", err)
		}
	****/
	err = xlFile.Save(excelFileName)
	//err = xlFile.Save()
	LogPrintErrAndExit("ошибка сохранения файла "+excelFileName, err)
	return nil
}

//func export_flats_fill_house(sheet *xlsx.Sheet, opt map[string]interface{}) error {
func export_flats_fill_house2(sheet *xlsx2.File, opt map[string]interface{}) error {
	return nil
	sheet_name := opt["cur_i_sheet_name"].(string)
	query := `
SELECT 
  /*'['||nh.fcomp || '] '||cn.name AS "УК",*/
  nh.street||' '||h.house AS "дом",
  h.fiasguid AS "код ФИАС",
  '87715000001' AS "OKTMO",
  'Исправный' AS "состояние",
  CAST(COALESCE(nh.ob_area,(SELECT MAX(t.ob_area) FROM t_kv2_nachisl_info_house t WHERE t.fcpsh='70-'||nh.fperiod||'-'||nh.fstrcode_house)) AS VARCHAR(10)) AS "общ.площадь",
  CAST(h.build_year AS VARCHAR(10)) AS "год постройки",
  CAST(h.floor_cnt AS VARCHAR(10)) AS "количество этажей",
  '0'  AS "количество подземных этажей",
  CAST(h.floor_cnt AS VARCHAR(10)) AS "минимальное количество этажей",
  'Москва' AS "часовая зона",
  'Нет' AS "объект культурного наследия",
  IIF(COALESCE(h.kadastrn1,'')!='',kadastrn1,'::'||h.strcode||':'||h.house||':'||h.build_year) AS "ГКН"
FROM
  t_kv2_nachisl_info_house nh
  LEFT JOIN t_obj_house h ON h.strcode=nh.strcode AND h.house=nh.house2
  LEFT JOIN company_name cn ON cn.ncomp=nh.fcomp
WHERE 1=1
  AND nh.fcomp = ` + opt["fcomp"].(string) + `
  AND nh.fperiod LIKE '` + opt["fperiod"].(string) + `'
  AND nh.prc_has_nachisl > 30
ORDER BY "дом","ГКН","код ФИАС"
	`
	rows, err := db.Query(query)
	LogPrintErrAndExit("db.Query error: \n"+query+"\n\n", err)

	n_row := 0
	for rows.Next() {

		var f1, f2, f3, f4, f5, f6, f7, f8, f9, f10, f11, f12 NullString

		if err := rows.Scan(&f1, &f2, &f3, &f4, &f5, &f6, &f7, &f8, &f9, &f10, &f11, &f12); err != nil {
			LogPrintErrAndExit("ERROR rows.Scan: \n"+query+"\n\n", err)
		}

		/*********
		sr := sheet.AddRow()
		sr.AddCell().SetString(f1.get(""))
		sr.AddCell().SetString(f1.get(""))
		sr.AddCell().SetString(f2.get(""))
		sr.AddCell().SetString(f3.get(""))
		sr.AddCell().SetString(f4.get(""))
		sr.AddCell().SetString(f5.get(""))
		sr.AddCell().SetString(f6.get(""))
		sr.AddCell().SetString(f7.get(""))
		sr.AddCell().SetString(f8.get(""))
		sr.AddCell().SetString(f9.get(""))
		sr.AddCell().SetString(f10.get(""))
		sr.AddCell().SetString(f11.get(""))
		sr.AddCell().SetString(f12.get(""))
		***********/
		sheet.SetCellStr(sheet_name, Fmts("A%d", n_row+2), f1.get(""))
		n_row++
	}
	LogPrint(Fmts("выгружено строк: %d", n_row))
	return nil
}

func export_flats_fill_house1(sheet *xlsx.Sheet, opt map[string]interface{}) error {
	return nil
	query := `
SELECT 
  /*'['||nh.fcomp || '] '||cn.name AS "УК",*/
  nh.street||' '||h.house AS "дом",
  h.fiasguid AS "код ФИАС",
  '87715000001' AS "OKTMO",
  'Исправный' AS "состояние",
  CAST(COALESCE(nh.ob_area,(SELECT MAX(t.ob_area) FROM t_kv2_nachisl_info_house t WHERE t.fcpsh='70-'||nh.fperiod||'-'||nh.fstrcode_house)) AS VARCHAR(10)) AS "общ.площадь",
  CAST(h.build_year AS VARCHAR(10)) AS "год постройки",
  CAST(h.floor_cnt AS VARCHAR(10)) AS "количество этажей",
  '0'  AS "количество подземных этажей",
  CAST(h.floor_cnt AS VARCHAR(10)) AS "минимальное количество этажей",
  'Москва' AS "часовая зона",
  'Нет' AS "объект культурного наследия",
  IIF(COALESCE(h.kadastrn1,'')!='',kadastrn1,'::'||h.strcode||':'||h.house||':'||h.build_year) AS "ГКН"
FROM
  t_kv2_nachisl_info_house nh
  LEFT JOIN t_obj_house h ON h.strcode=nh.strcode AND h.house=nh.house2
  LEFT JOIN company_name cn ON cn.ncomp=nh.fcomp
WHERE 1=1
  AND nh.fcomp = ` + opt["fcomp"].(string) + `
  AND nh.fperiod LIKE '` + opt["fperiod"].(string) + `'
  AND nh.prc_has_nachisl > 30
ORDER BY "дом","ГКН","код ФИАС"
	`
	rows, err := db.Query(query)
	LogPrintErrAndExit("db.Query error: \n"+query+"\n\n", err)

	n_row := 0
	for rows.Next() {

		var f1, f2, f3, f4, f5, f6, f7, f8, f9, f10, f11, f12 NullString

		if err := rows.Scan(&f1, &f2, &f3, &f4, &f5, &f6, &f7, &f8, &f9, &f10, &f11, &f12); err != nil {
			LogPrintErrAndExit("ERROR rows.Scan: \n"+query+"\n\n", err)
		}

		/*********/
		sr := sheet.AddRow()
		sr.AddCell().SetString(f1.get(""))
		sr.AddCell().SetString(f1.get(""))
		sr.AddCell().SetString(f2.get(""))
		sr.AddCell().SetString(f3.get(""))
		sr.AddCell().SetString(f4.get(""))
		sr.AddCell().SetString(f5.get(""))
		sr.AddCell().SetString(f6.get(""))
		sr.AddCell().SetString(f7.get(""))
		sr.AddCell().SetString(f8.get(""))
		sr.AddCell().SetString(f9.get(""))
		sr.AddCell().SetString(f10.get(""))
		sr.AddCell().SetString(f11.get(""))
		sr.AddCell().SetString(f12.get(""))
		/***********/
		//sheet.SetCellStr(sheet_name, Fmts("A%d", n_row+2), f1.get(""))
		n_row++
	}
	LogPrint(Fmts("выгружено строк: %d", n_row))
	return nil
}
