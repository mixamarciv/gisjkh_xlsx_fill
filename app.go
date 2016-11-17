package main

import (
	"fmt"
	"strconv"
	"time"

	//xmlx "github.com/jteeuwen/go-pkg-xmlx"
	mf "github.com/mixamarciv/gofncstd3000"

	"io/ioutil"
	"os"
	"strings"

	"path"

	flags "github.com/jessevdk/go-flags"

	structs "github.com/fatih/structs"

	xlsx2 "github.com/Luxurioust/excelize"
	"github.com/tealeg/xlsx"
)

type xlsxfillfnc func(map[string]interface{}) error

type xlsxlistfillfnc1 func(*xlsx.Sheet, map[string]interface{}) error
type xlsxlistfillfnc2 func(*xlsx2.File, map[string]interface{}) error

var mapFnc map[string]xlsxfillfnc = make(map[string]xlsxfillfnc)            //map функций для обработки xlsx файлов
var mapFnc2 map[string]xlsxlistfillfnc1 = make(map[string]xlsxlistfillfnc1) //map функций для обработки листов

var Fmts = fmt.Sprintf

//var Print = fmt.Print
var Itoa = strconv.Itoa

func main() {
	startTime := time.Now()
	Initdb()

	opt := parseInputArgs([]string{"type", "from", "to"})

	opt["from"] = mf.PathClean(opt["from"].(string))
	opt["to"] = mf.PathClean(opt["to"].(string))
	copyFile(opt["from"].(string), opt["to"].(string))

	fncname := opt["type"].(string)
	fnc, ok := mapFnc[fncname]
	if !ok {
		LogPrintAndExit("ошибка нет такого типа операции type==\"" + fncname + "\"")
	}

	startRenderTime := time.Now()

	LogPrint("Обработка файла \"" + opt["to"].(string) + "\"")
	fnc(opt)

	LogPrint(Fmts("render/total time: %v / %v", time.Now().Sub(startRenderTime), time.Now().Sub(startTime)))
}

func copyFile(src string, dst string) {
	data, err := ioutil.ReadFile(src)
	LogPrintErrAndExit("ошибка чтения файла "+src, err)

	dst_dir := path.Dir(dst)
	err = mf.MkdirAll(dst_dir)
	LogPrint("создан каталог: " + dst_dir)
	LogPrintErrAndExit("ошибка доступа/создания каталога "+dst_dir, err)

	err = ioutil.WriteFile(dst, data, 0644)
	LogPrintErrAndExit("ошибка записи файла "+dst, err)
}

type Opts struct {
	Type    string `long:"type" description:"xlsx type"`
	From    string `long:"from" description:"full path to input xlsx file"`
	To      string `long:"to" description:"full path to out xlsx file"`
	Fcomp   string `long:"fcomp" description:"fcomp"`
	Fperiod string `long:"fperiod" description:"fperiod"`
}

//разбор параметров и перевод их в map[string]interface{} в нижнем регистре и проверка на наличие обязательных параметров need
func parseInputArgs(need []string) map[string]interface{} {
	var opts Opts
	_, err := flags.ParseArgs(&opts, os.Args)
	LogPrintErrAndExit("ошибка разбора параметров", err)

	options := structs.Map(opts)

	//все параметры в нижнем регистре:
	for key, val := range options {
		lkey := strings.ToLower(key)
		if key != strings.ToLower(key) {
			options[lkey] = val
			delete(options, key)
		}
	}

	checkOptionsAndExit(options, need)
	return options
}

//проверка заданы ли обязательные параметры need в options, если нет то сообщаем об ошибке и завершаем выполнение
func checkOptionsAndExit(options map[string]interface{}, need []string) {
	var notfound []string = nil
	var strnotfound string = ""
	for _, param := range need {
		if val, ok := options[param]; !ok || val == nil || val == "" {
			notfound = append(notfound, param)
			strnotfound = strnotfound + " " + param
		}
	}
	if len(notfound) > 0 {
		LogPrint("ОШИБКА: не заданы обязательные параметры: " + strnotfound)
		LogPrint(Fmts("список указанных параметров: %v", options))
		os.Exit(1)
	}
}
