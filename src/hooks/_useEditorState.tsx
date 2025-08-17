import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';

export default function useEditorState() {
	const { recipeId, isSavingPost, isAutosavingPost } = useSelect(
		( select ) => {
			const editor = select( editorStore );
			return {
				recipeId: editor.getCurrentPostId(),
				isSavingPost: editor.isSavingPost(),
				isAutosavingPost: editor.isAutosavingPost(),
			};
		},
		[]
	);
	const { blocks } = useSelect( ( select ) => {
		const editor = select( blockEditorStore );
		return {
			blocks: editor.getBlocks(),
		};
	}, [] );
	return { recipeId, isSavingPost, isAutosavingPost, blocks };
}
