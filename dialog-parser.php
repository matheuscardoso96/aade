<?php
require_once('utils/aade.php');

$filename = $_FILES['script-file']['name'];
$path = $_FILES['script-file']['tmp_name'];

$extension = explode('.', $filename);
$extension = strtolower( end($extension) );
$filename_without_extension = str_replace('.' . $extension, '', $filename);

if($extension != 'txt'){
	die('Formato inválido.');
}
if(!file_exists($path)){
	die('Erro ao carregar arquivo transferido para o servidor.');
}

$file = file($path);

// Separating strings in sections
$number = -1;
$sections = $sections_blocks = array();
foreach($file as $line){
	$checkDialogueChanged = preg_match('/\{\{[0-9]+\}\}/', $line);
	if($checkDialogueChanged){
		$expression = preg_match('/\{\{[0-9]+\}\}/', $line, $results);
		$number = str_replace(array('{', '}'), '', $results[0]);
		$number = (int)$number;
	}
	
	if($number > -1){
		$line = str_replace('{{' . $number . '}}', '', $line);
		
		if(!isset($sections[$number])){
			$sections[$number] = $line;
		} else {
			$sections[$number] .= $line;
		}
	}
}

$tag = false;
$character_code = $tag_text = '';

// Iterating into sections to separate them into blocks
foreach($sections as $section_number=>$section){
	$chars_section = str_split($section);
	$block_number = 1;
	
	// Iterating current section, char by char
	foreach($chars_section as $char){
		if($char == '{'){
			$tag = true;
		} elseif($char == '}'){
			$tag = false;
		}
		
		if(!isset($sections_blocks[$section_number])){
			$sections_blocks[$section_number] = array();
		}
		if(!isset($sections_blocks[$section_number][$block_number])){
			$sections_blocks[$section_number][$block_number] = array();
		}
		if(!isset($sections_blocks[$section_number][$block_number]['character_code'])){
			$sections_blocks[$section_number][$block_number]['character_code'] = $character_code;
		}
		if(!isset($sections_blocks[$section_number][$block_number]['text'])){
			$sections_blocks[$section_number][$block_number]['text'] = $char;
		} else {
			$sections_blocks[$section_number][$block_number]['text'] .= $char;
		}
		if(!isset($sections_blocks[$section_number][$block_number]['has_endjmp'])){
			$sections_blocks[$section_number][$block_number]['has_endjmp'] = false;
		}
		
		if($tag){
			if($char != '{'){
				$tag_text .= $char;
			}
		} else {
			if(aade::startsWith($tag_text, 'name:')){
				$tmp = explode(':', $tag_text);
				$character_code = trim( end($tmp) );
				
				$sections_blocks[$section_number][$block_number]['character_code'] = $character_code;
			}
			
			$checkHasEndJump = ($tag_text == 'endjmp');
			if($checkHasEndJump){
				$sections_blocks[$section_number][$block_number]['has_endjmp'] = true;
			}
			
			$checkBreakDetected = in_array($tag_text, array('p', 'nextpage_button', 'nextpage_nobutton'));
			if($checkBreakDetected){
				$block_number++;
			}
			$tag_text = '';
		}
	}
}
?>
<div id="global-actions-dropdown" class="dropdown pull-right">
	<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		Arquivo
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu">
		<li>
			<a href="#" onclick="aade.showScriptConfigSettings()">
				<span class="glyphicon glyphicon-cog"></span>
				Configurações
			</a>
		</li>
		<li>
			<a href="#" onclick="aade.previewScript()">
				<span class="glyphicon glyphicon-search"></span>
				Gerar Prévia do Script
			</a>
		</li>
		<li>
			<a href="#" onclick="aade.saveScript()">
				<span class="glyphicon glyphicon-save-file"></span>
				Salvar Script
			</a>
		</li>
	</ul>
</div>
<table id="dialog-parser-table" class="table table-striped table-bordered" data-filename="<?php echo $filename_without_extension ?>">
	<thead>
		<tr>
			<th class="hidden-xs">Ordem</th>
			<th class="hidden-xs">Seção</th>
			<th class="hidden-xs">Número</th>
			<th>Bloco</th>
			<th class="hidden-xs">Prévia</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$total_dialog_blocks = 0;
		$total_sections = 0;
		foreach($sections_blocks as $section_number=>$blocks){
			$total_sections++;
			foreach($blocks as $block_number=>$block){
				$total_dialog_blocks++;
				
				$textareaOrder = $total_dialog_blocks;
				$dialogId = "s{$section_number}-b{$total_dialog_blocks}-dialog";
				
				$text = rtrim($block['text']);
				$characterCode = $block['character_code'];
				$checkHasEndJump = $block['has_endjmp'];
				?>
				<tr>
					<td class="hidden-xs order"><?php echo $total_dialog_blocks ?></td>
					<td class="hidden-xs section">{{<?php echo $section_number ?>}}</td>
					<td class="hidden-xs block-number"><?php echo $block_number ?></td>
					<td class="form-fields">
						<div class="row visible-xs">
							<div class="col-xs-4">
								<b>Ordem:</b> <span class="order"><?php echo $total_dialog_blocks ?></span><br />
								<b>Seção:</b> <span class="section">{{<?php echo $section_number ?>}}</span><br />
								<b>Número:</b> <span class="block-number"><?php echo $block_number ?></span>
							</div>
							<div class="col-xs-8">
								<div class="btn-group btn-group-sm" role="group" aria-label="Ações">
									<button class="btn btn-info" onclick="aade.showPreviewOnMobile(this)">
										<span class="glyphicon glyphicon-search"></span>
									</button>
								</div>
							</div>
						</div>
						<textarea class="form-control text-field" data-order="<?php echo $textareaOrder ?>"
							data-section="<?php echo $section_number ?>" data-block="<?php echo $block_number ?>"
							onkeyup="aade.updatePreview(this, '<?php echo $dialogId ?>', 't', false)"><?php echo $text ?></textarea>		
					</td>
					<td class="preview-conteiners hidden-xs">
						<div class="row visible-xs" style="padding-bottom: 5px">
							<div class="col-xs-4">
								<b>Ordem:</b> <span class="order"><?php echo $total_dialog_blocks ?></span><br />
								<b>Seção:</b> <span class="section">{{<?php echo $section_number ?>}}</span><br />
								<b>Número:</b> <span class="block-number"><?php echo $block_number ?></span>
							</div>
							<div class="col-xs-8">
								<div class="btn-group btn-group-sm" role="group" aria-label="Ações">
									<button class="btn btn-info" onclick="aade.showPreviewOnMobile(this)">
										<span class="glyphicon glyphicon-search"></span>
									</button>
									<button class="btn btn-warning copy-clipboard">
										<span class="glyphicon glyphicon-copy"></span>
									</button>
									<button class="btn btn-primary render-image" onclick="aade.renderPreviewImageOnBrowser(this)">
										<span class="glyphicon glyphicon-picture"></span>
									</button>
									<?php if(!$checkHasEndJump){ ?>
									<button class="btn btn-success add-new-block" tabindex="-1" title="Adicionar novo bloco de diálogo"
										onclick="aade.addNewDialogBlock(this)">
										<span class="glyphicon glyphicon-plus"></span>
									</button>
								<?php } ?>
								</div>
							</div>
						</div>
						<div id="<?php echo $dialogId ?>" class="dialog-preview text-only">
							<div class="character-name" data-character-code="<?php echo $characterCode ?>"></div>
							<div class="btn-group btn-group-xs hidden-xs" role="group" aria-label="Ações Mobile">
								<button class="btn btn-warning copy-clipboard" tabindex="-1">
									<span class="glyphicon glyphicon-copy"></span>
								</button>
								<button class="btn btn-primary render-image" tabindex="-1" title="Gerar Imagem"
									onclick="aade.renderPreviewImageOnBrowser(this)">
									<span class="glyphicon glyphicon-picture"></span>
								</button>
								<?php if(!$checkHasEndJump){ ?>
									<button class="btn btn-success add-new-block" tabindex="-1" title="Adicionar novo bloco de diálogo"
										onclick="aade.addNewDialogBlock(this)">
										<span class="glyphicon glyphicon-plus"></span>
									</button>
								<?php } ?>
							</div>
							<div class="text-window"></div>
						</div>
					</td>
				</tr>
			<?php
			}
		} ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="5">
				Total de seções: <?php echo $total_sections ?> - Total de diálogos: <span class="total-dialog-blocks"><?php echo $total_dialog_blocks ?></span>
			</td>
		</tr>
	</tfoot>
</table>